<?php
namespace app\services;

/** Product.quantity/rest are materialized by database triggers for every reader. */
final class StockAdjustment
{
    public static function adminIndicators(array $product, array $modifications = []): string
    {
        $html = '';
        foreach (array_merge([$product], $modifications) as $index => $item) {
            $adjustment = (int)($item['stock_adjustment'] ?? 0);
            if ($adjustment === 0) continue;
            $signed = $adjustment > 0 ? '+' . $adjustment : '−' . abs($adjustment);
            $label = $index === 0 ? 'Корректировка' : 'Модификация ' . (string)($item['article'] ?? '');
            $html .= '<div class="mt-2"><span class="badge badge-warning">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ': ' . $signed . '</span>'
                . '<small class="d-block text-muted">До: ' . (int)($item['inventory_quantity'] ?? 0)
                . ' → итог: ' . (int)($item['quantity'] ?? 0) . '</small></div>';
        }
        return $html;
    }

    public static function parse($value): int
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[+-]?\d+$/D', trim((string)$value))
            || (float)$value < -1000000000 || (float)$value > 1000000000) {
            throw new \InvalidArgumentException('Корректировка наличия должна быть целым числом от −1000000000 до 1000000000.');
        }
        return (int)$value;
    }

    public static function triggerBody(bool $modification = false): string
    {
        if ($modification) {
            return 'BEGIN
                SET NEW.inventory_quantity = COALESCE(NEW.inventory_quantity, NEW.quantity, 0);
                SET NEW.quantity = GREATEST(0, NEW.inventory_quantity + NEW.stock_adjustment);
            END';
        }
        return "BEGIN
            SET NEW.inventory_quantity = COALESCE(NEW.inventory_quantity, NEW.quantity, 0);
            SET NEW.inventory_rest = COALESCE(NEW.inventory_rest, NEW.rest, NEW.inventory_quantity, 0);
            SET NEW.quantity = GREATEST(0, NEW.inventory_quantity + NEW.stock_adjustment);
            SET NEW.rest = GREATEST(0, NEW.inventory_rest + NEW.stock_adjustment);
            IF NEW.quantity = 0 AND NEW.stock_status_id = 1 THEN
                SET NEW.stock_status_id = 0;
            ELSEIF NEW.quantity > 0 AND NEW.stock_status_id = 0 THEN
                SET NEW.stock_status_id = 1;
            END IF;
        END";
    }

    public static function migrate(): void
    {
        $columns = array_column(\R::getAll('SHOW COLUMNS FROM product'), 'Field');
        foreach (['inventory_quantity' => 'INT NULL', 'inventory_rest' => 'INT NULL',
            'stock_adjustment' => 'INT NOT NULL DEFAULT 0'] as $name => $type) {
            if (!in_array($name, $columns, true)) \R::exec("ALTER TABLE product ADD `$name` $type");
        }
        \R::exec('UPDATE product SET inventory_quantity = quantity WHERE inventory_quantity IS NULL');
        \R::exec('UPDATE product SET inventory_rest = rest WHERE inventory_rest IS NULL');
        foreach (['insert', 'update'] as $event) {
            $name = 'product_stock_adjustment_' . $event;
            if (!\R::getCell('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = ?', [$name])) {
                \R::exec("CREATE TRIGGER $name BEFORE " . strtoupper($event) . ' ON product FOR EACH ROW ' . self::triggerBody());
            }
        }
        $columns = array_column(\R::getAll('SHOW COLUMNS FROM modification'), 'Field');
        foreach (['inventory_quantity' => 'INT NULL', 'stock_adjustment' => 'INT NOT NULL DEFAULT 0'] as $name => $type) {
            if (!in_array($name, $columns, true)) \R::exec("ALTER TABLE modification ADD `$name` $type");
        }
        \R::exec('UPDATE modification SET inventory_quantity = quantity WHERE inventory_quantity IS NULL');
        foreach (['insert', 'update'] as $event) {
            $name = 'modification_stock_adjustment_' . $event;
            if (!\R::getCell('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = ?', [$name])) {
                \R::exec("CREATE TRIGGER $name BEFORE " . strtoupper($event) . ' ON modification FOR EACH ROW ' . self::triggerBody(true));
            }
        }
    }
}
