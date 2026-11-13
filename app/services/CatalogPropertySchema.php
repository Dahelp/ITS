<?php
namespace app\services;

/** Explicit installer only; never called by an ordinary page request. All tables are isolated. */
final class CatalogPropertySchema
{
    public static function install(\PDO $db): void
    {
        $tables = [
            'catalog_foundation_version' => 'id INT UNSIGNED NOT NULL PRIMARY KEY, version INT NOT NULL',
            'catalog_type' => 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, code VARCHAR(64) NOT NULL UNIQUE, label VARCHAR(255) NOT NULL',
            'catalog_property' => 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, code VARCHAR(64) NOT NULL UNIQUE, label VARCHAR(255) NOT NULL, kind VARCHAR(32) NOT NULL, unit_code VARCHAR(16) NOT NULL DEFAULT \'\', definition_json MEDIUMTEXT NOT NULL',
            'catalog_property_option' => 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, property_id INT UNSIGNED NOT NULL, code VARCHAR(64) NOT NULL, label VARCHAR(255) NOT NULL, UNIQUE KEY property_code(property_id,code), UNIQUE KEY option_property(id,property_id), FOREIGN KEY(property_id) REFERENCES catalog_property(id)',
            'catalog_property_rule' => 'type_id INT UNSIGNED NOT NULL, property_id INT UNSIGNED NOT NULL, applicability VARCHAR(24) NOT NULL, PRIMARY KEY(type_id,property_id), FOREIGN KEY(type_id) REFERENCES catalog_type(id), FOREIGN KEY(property_id) REFERENCES catalog_property(id)',
            'catalog_product_profile' => 'product_id INT UNSIGNED NOT NULL PRIMARY KEY, type_id INT UNSIGNED NOT NULL, version INT UNSIGNED NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL, FOREIGN KEY(product_id) REFERENCES product(id), FOREIGN KEY(type_id) REFERENCES catalog_type(id)',
            'catalog_product_property' => 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, property_id INT UNSIGNED NOT NULL, value_state VARCHAR(24) NOT NULL, verification VARCHAR(24) NOT NULL, numeric_value DECIMAL(16,6) NULL, text_value TEXT NULL, bool_value TINYINT NULL, source_ref TEXT NOT NULL, UNIQUE KEY product_property(product_id,property_id), UNIQUE KEY value_property(id,property_id), FOREIGN KEY(product_id) REFERENCES catalog_product_profile(product_id), FOREIGN KEY(property_id) REFERENCES catalog_property(id)',
            'catalog_product_property_option' => 'value_id INT UNSIGNED NOT NULL, property_id INT UNSIGNED NOT NULL, option_id INT UNSIGNED NOT NULL, PRIMARY KEY(value_id,option_id), FOREIGN KEY(value_id,property_id) REFERENCES catalog_product_property(id,property_id) ON DELETE CASCADE, FOREIGN KEY(option_id,property_id) REFERENCES catalog_property_option(id,property_id)',
            'catalog_product_size' => 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, normalized_size VARCHAR(80) NOT NULL, role VARCHAR(24) NOT NULL, verification VARCHAR(24) NOT NULL, source_ref TEXT NOT NULL, conditions_text TEXT NOT NULL, UNIQUE KEY product_size(product_id,normalized_size), FOREIGN KEY(product_id) REFERENCES catalog_product_profile(product_id)',
            'catalog_tire_service_rating' => 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, sequence_no INT UNSIGNED NOT NULL, raw_marking VARCHAR(255) NOT NULL, load_single INT NULL, load_dual INT NULL, speed_symbol VARCHAR(4) NULL, operating_mode VARCHAR(32) NOT NULL, pressure_kpa DECIMAL(10,3) NULL, verification VARCHAR(24) NOT NULL, source_ref TEXT NOT NULL, UNIQUE KEY product_sequence(product_id,sequence_no), FOREIGN KEY(product_id) REFERENCES catalog_product_profile(product_id)',
            'catalog_property_revision' => 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, actor_id INT UNSIGNED NOT NULL, before_json MEDIUMTEXT NOT NULL, after_json MEDIUMTEXT NOT NULL, changed_at DATETIME NOT NULL, KEY product_history(product_id,id)',
            'catalog_legacy_map' => 'id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, source_kind VARCHAR(32) NOT NULL, source_id INT UNSIGNED NOT NULL, property_id INT UNSIGNED NOT NULL, transform_code VARCHAR(64) NOT NULL, UNIQUE KEY source_property(source_kind,source_id,property_id), FOREIGN KEY(property_id) REFERENCES catalog_property(id)'
        ];
        $found=$db->query("SHOW TABLES LIKE 'catalog_foundation_version'")->fetchColumn();
        if ($found) {
            $version=$db->query('SELECT version FROM catalog_foundation_version WHERE id=1')->fetchColumn();
            if ($version!==false) {
                if ((int)$version!==1) throw new \RuntimeException('Unsupported catalogue schema version.');
            }
        }
        foreach ($tables as $name=>$body) $db->exec('CREATE TABLE IF NOT EXISTS '.$name.' ('.$body.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        // Once managed, runtime definitions in DB must never be overwritten by seed JSON.
        CatalogProperties::useDictionary($db);
        $defs=CatalogProperties::definitions();
        $db->beginTransaction();
        try {
            $insert=$db->prepare('INSERT INTO catalog_type(code,label) VALUES(?,?) ON DUPLICATE KEY UPDATE code=VALUES(code)');
            foreach (CatalogProperties::types() as $code=>$label) $insert->execute([$code,$label]);
            $types=$db->query('SELECT code,id FROM catalog_type')->fetchAll(\PDO::FETCH_KEY_PAIR);
            $insert=$db->prepare('INSERT INTO catalog_property(code,label,kind,unit_code,definition_json) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE label=VALUES(label),definition_json=VALUES(definition_json)');
            foreach ($defs as $code=>$def) {
                $check=$db->prepare('SELECT kind,unit_code FROM catalog_property WHERE code=?');$check->execute([$code]);$existing=$check->fetch(\PDO::FETCH_ASSOC);
                if($existing&&($existing['kind']!==$def['kind']||$existing['unit_code']!==($def['unit']??'')))throw new \RuntimeException('Changing property type or unit requires a separate migration: '.$code);
                $insert->execute([$code,$def['label'],$def['kind'],$def['unit']??'',json_encode($def,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
                $q=$db->prepare('SELECT id FROM catalog_property WHERE code=?');$q->execute([$code]);$id=(int)$q->fetchColumn();
                $option=$db->prepare('INSERT INTO catalog_property_option(property_id,code,label) VALUES(?,?,?) ON DUPLICATE KEY UPDATE code=VALUES(code)');
                foreach ($def['options']??[] as $key=>$label) $option->execute([$id,$key,$label]);
                $rule=$db->prepare('INSERT INTO catalog_property_rule(type_id,property_id,applicability) VALUES(?,?,?) ON DUPLICATE KEY UPDATE applicability=VALUES(applicability)');
                foreach ($types as $type=>$typeId) $rule->execute([$typeId,$id,CatalogProperties::applicability($code,$type)]);
                $map=$db->prepare('INSERT INTO catalog_legacy_map(source_kind,source_id,property_id,transform_code) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE source_id=VALUES(source_id)');
                foreach ($def['legacy_attribute_ids']??[] as $old) $map->execute(['attribute',$old,$id,'reviewed_candidate']);
                foreach ($def['legacy_filter_group_ids']??[] as $old) $map->execute(['filter_group',$old,$id,'reviewed_candidate']);
            }
            $db->exec('INSERT INTO catalog_foundation_version(id,version) VALUES(1,1) ON DUPLICATE KEY UPDATE version=VALUES(version)');
            $db->commit();
        } catch (\Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
    }
}
