<?php

namespace app\controllers\admin;

use app\models\admin\Company;
use app\models\AppModel;
use ishop\App;
use ishop\libs\Pagination;
use Element\TextRun;
use TemplateProcessor as Tpl;
use PhpWord;
use IOFactory;
use Element\Section;
use Element\Table;

class CompanyController extends AppController {

    public function indexAction(){
        $company = \R::getAll("SELECT * FROM company ORDER BY comp_name");
        $this->setMeta('Список компаний');
        $this->set(compact('company'));
    }
	
	public function deleteAction(){
        $id = $this->getRequestID();		
		$typeprice = \R::findAll('company_typeprice', 'company_id = ?', [$id]);
		foreach($typeprice as $type) {
			$delete_type = \R::load('company_typeprice', $type->id);
			\R::trash($delete_type);
		}
        $company = \R::load('company', $id);
        \R::trash($company);
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','35','company','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
        $_SESSION['success'] = 'Компания '.$company["comp_name"].' удалена';
        redirect();
    }
	
	public function addAction(){
        if(!empty($_POST)){
            $company = new Company();
            $data = $_POST;			
            $company->load($data);
			
            if(!$company->validate($data) || !$company->checkUnique()){
                $company->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($id = $company->save('company')){
				$company->editCompanyTypeprice($id, $data);
				\R::exec("UPDATE user SET comp_id = '".$id."' WHERE id = ?", [$company->attributes['user_id']]);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','33','company','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                $_SESSION['success'] = 'Компания добавлена';
            }
            redirect();
        }
        $this->setMeta('Новая компания');
    }
	
	public function editAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $company = new Company();
            $data = $_POST;
            $company->load($data);

            if(!$company->validate($data)){
                $company->getErrors();
                redirect();
            }
            if($company->update('company', $id)){
				$company->editCompanyTypeprice($id, $data);
				\R::exec("UPDATE user SET comp_id = '".$id."' WHERE id = ?", [$company->attributes['user_id']]);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','34','company','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                $_SESSION['success'] = 'Изменения сохранены';
                redirect();
            }
        }

        $id = $this->getRequestID();
        $company = \R::load('company', $id);
      	$usercontact = \R::findOne('user', 'id = ?', [$company->user_id]);
		$cat_priceopt = \R::getAll('SELECT company_typeprice.category_id, category.name AS category_name, company_typeprice.znachenie AS znachenie FROM company_typeprice, category WHERE category.id = company_typeprice.category_id AND company_typeprice.company_id = ?', [$id]);
        $this->setMeta("Редактирование компании {$company->comp_name}");
        $this->set(compact('company', 'usercontact', 'cat_priceopt'));
    }
	
	
	public function innsAction(){
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $data['items'] = [];
        $companys = \R::getAssoc('SELECT id, comp_name, inn FROM company WHERE concat(comp_name, inn) LIKE ? LIMIT 10', ["%{$q}%"]);
        if($companys){
            $i = 0;
            foreach($companys as $id => $name){
                $data['items'][$i]['id'] = $id;
                $data['items'][$i]['text'] = "".$name["comp_name"]." (".$name["inn"].")";
                $i++;
            }
        }
        echo json_encode($data);
        die;
    }
	
	public function wordAction(){
        
        $this->setMeta('Карточка компании');
        
    }
	
	public function cardcompanywordAction(){
		$comp_id = $_GET["id"];
		$comp = \R::findOne("company", "id = ?", [$comp_id]);
		$user = \R::findOne("user", "id = ?", [$comp["user_id"]]);
        $this->set(compact('comp', 'user'));
	}

}