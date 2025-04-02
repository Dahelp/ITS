<?php

namespace app\controllers\admin;

use app\models\admin\User;
use app\models\admin\UserCustomer;
use app\models\admin\UserRole;
use app\models\admin\UserGroup;
use app\models\AppModel;
use ishop\App;
use ishop\libs\Pagination;
use ishop\base\Controller;

class UserController extends AppController {

    public function indexAction(){
        $users = \R::getAll("SELECT user.id, user.email, user.name, user.date_last_visit, user_groups.name AS groups, user.admin_id, user.comp_id FROM user, user_groups, roles WHERE user.role = roles.alt_name AND user.groups = user_groups.id AND user.role = 'user'");
        $this->setMeta('Список клиентов');
        $this->set(compact('users'));
    }
	
	public function rolesAction(){
        $count = \R::count('roles');
        $roles = \R::getAll("SELECT * FROM roles");
        $this->setMeta('Список ролей');
        $this->set(compact('roles', 'count'));
    }
	
	public function groupsAction(){
        $count = \R::count('user_groups');
        $groups = \R::getAll("SELECT * FROM user_groups");
        $this->setMeta('Список групп пользователей');
        $this->set(compact('groups', 'count'));
    }

    public function addAction(){
		if(!empty($_POST)){
            $user = new User();
            $data = $_POST;
            $user->load($data);
            if(!$user->validate($data) || !$user->checkUnique()){
                $user->getErrors();
                $_SESSION['form_data'] = $data;
            }else{
                $user->attributes['password'] = password_hash($user->attributes['password'], PASSWORD_DEFAULT);
                if($id = $user->save('user')){
					if($data["newsletter"] == 1) {
						$user->editNewsletterGroup($id, $data);
					}
					\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','36','user','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                    $_SESSION['success'] = 'Пользователь зарегистрирован';
                }else{
                    $_SESSION['error'] = 'Ошибка!';
                }
            }
            redirect();
        }
        $this->setMeta('Новый клиент');
		$user_groups = \R::getAll("SELECT * FROM user_groups WHERE role = 'user'");
        $this->set(compact('user_groups'));
    }
	
	public function addRoleAction(){
		if(!empty($_POST)){
            $role = new UserRole();
            $data = $_POST;
            $role->load($data);
            if(!$role->validate($data)){
                $role->getErrors();
                redirect();
            }
            if($role->save('roles', false)){
                $_SESSION['success'] = 'Роль добавлена';
                redirect();
            }
        }
        $this->setMeta('Новая роль');
    }
	
	public function addGroupAction(){
		if(!empty($_POST)){
            $group = new UserGroup();
            $data = $_POST;
            $group->load($data);
            if(!$group->validate($data)){
                $group->getErrors();
                redirect();
            }
            if($group->save('user_groups', false)){
                $_SESSION['success'] = 'Роль добавлена';
                redirect();
            }
        }
        $this->setMeta('Новая роль');
    }

    public function editAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $user = new \app\models\admin\User();
            $data = $_POST;
            $user->load($data);
            if(!$_POST['confirm_password']){
                unset($_POST['new_password']);
            }else{
                if($_POST['new_password'] == $_POST['confirm_password']) {
					$user->attributes['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
				}else{
					$_SESSION['error'] = 'Пароли не совпадают';
					redirect();
				}
            }
            if(!$user->validate($data) || !$user->checkUnique()){
                $user->getErrors();
                redirect();
            }
            if($user->update('user', $id)){
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','37','user','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
		$user_groups = \R::getAll("SELECT * FROM user_groups WHERE role = 'user'");
        $user_id = $this->getRequestID();
        $user = \R::load('user', $user_id);
		$newsletters = \R::getAll("SELECT * FROM user_newsletter, newsletter WHERE user_newsletter.newsletter_id = newsletter.id AND user_newsletter.user_id = '".$user_id."'");
        $orders = \R::getAll("SELECT `order`.`id`, `order`.`inv`, `order`.`comp_id`, `order`.`user_id`, `order`.`status`, `order`.`date`, `order`.`update_at`, `order`.`currency`, `order_status`.`status_name`, ROUND(SUM(`order_product`.`price` * `order_product`.`qty`), 2) AS `sum`
		FROM `order`, `order_product`, `order_status`
		WHERE`order`.`id` = `order_product`.`order_id` AND `order_status`.`id` = `order`.`status` AND `user_id` = {$user_id} GROUP BY `order`.`id` ORDER BY `order`.`status`, `order`.`id`");
		$useradmin = \R::findOne('user', 'id = ?', [$user->admin_id]);		
        $this->setMeta('Редактирование профиля клиента');
        $this->set(compact('user', 'orders', 'user_groups', 'newsletters', 'useradmin'));
    }
	
	public function editGroupAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $group = new \app\models\admin\UserGroup();
            $data = $_POST;
            $group->load($data);

            if(!$group->validate($data)){
                $group->getErrors();
                redirect();
            }
            if($group->update('user_groups', $id)){
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
		
        $group_id = $this->getRequestID();
        $group = \R::load('user_groups', $group_id);        

        $this->setMeta('Редактирование группы пользователей');
        $this->set(compact('group'));
    }
	
	public function editRoleAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $role = new \app\models\admin\UserRole();
            $data = $_POST;
            $role->load($data);

            if(!$role->validate($data)){
                $role->getErrors();
                redirect();
            }
            if($role->update('roles', $id)){
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
		
        $role_id = $this->getRequestID();
        $role = \R::load('roles', $role_id);        

        $this->setMeta('Редактирование роли пользователей');
        $this->set(compact('role'));
    }

    public function loginAdminAction(){
        if(!empty($_POST)){
            $user = new User();
            if(!$user->login(true)){
                $_SESSION['error'] = 'Почта/пароль введены неверно';
            }
            if(User::isAdmin()){
                redirect(ADMIN);
            }else{
                redirect();
            }
        }
        $this->layout = 'login';
		$shop_name = App::$app->getProperty('shop_name');
		$this->set(compact('shop_name'));
    }
	
	public function deleteAction(){
        $id = $this->getRequestID();
		$find = \R::findOne('user', 'id = ?',[$id]);
        $user = \R::load('user', $find->id);
		if($find->role == "admin") { $_SESSION['error'] = 'Нельзя удалить администратора';
		}else{		
			\R::trash($user);
			\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','38','user','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
			$_SESSION['success'] = 'Клиент '.$user["email"].' удален';			
		}
		redirect();
    }
	
	public function deleteGroupAction(){
        $id = $this->getRequestID();
		$find = \R::findOne('user_groups', 'id = ?',[$id]);
        $group = \R::load('user_groups', $find->id);
		\R::trash($group);
		$_SESSION['success'] = 'Группа '.$group["name"].' удалена';		
		redirect();
    }
	
	public function deleteRoleAction(){
        $id = $this->getRequestID();
		$find = \R::findOne('roles', 'id = ?',[$id]);
        $role = \R::load('roles', $find->id);
		\R::trash($role);
		$_SESSION['success'] = 'Роль '.$role["name"].' удалена';		
		redirect();
    }
	
	public function customersAction(){
        $users = \R::getAll("SELECT user.id, user.email, user.name, user.date_last_visit, user_groups.name AS groups FROM user, user_groups, roles WHERE user.role = roles.alt_name AND user.groups = user_groups.id AND user.role != 'user'");
        $this->setMeta('Список администраторов');
        $this->set(compact('users'));
    }
	
	public function addCustomerAction(){
		if(!empty($_POST)){
            $usercus = new UserCustomer();
			$user = new User();
            $data = $_POST;
            $usercus->load($data);
            if(!$usercus->validate($data) || !$usercus->checkUnique()){
                $usercus->getErrors();
                $_SESSION['form_data'] = $data;
            }else{
                $usercus->attributes['password'] = password_hash($usercus->attributes['password'], PASSWORD_DEFAULT);
                if($id = $usercus->save('user')){
					if($data["newsletter"] == 1) {
						$user->editNewsletterGroup($id, $data);
					}
					\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','39','user','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                    $_SESSION['success'] = 'Пользователь зарегистрирован';
                }else{
                    $_SESSION['error'] = 'Ошибка!';
                }
            }
            redirect();
        }
        $this->setMeta('Новый администратор');
		$user_groups = \R::getAll("SELECT * FROM user_groups WHERE role != 'user'");
        $this->set(compact('user_groups'));
    }

	public function editCustomerAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $user = new UserCustomer();
            $data = $_POST;			
            $user->load($data);
			if(!$_POST['confirm_password']){
                unset($_POST['new_password']);
            }else{
                if($_POST['new_password'] == $_POST['confirm_password']) {
					$user->attributes['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
				}else{
					$_SESSION['error'] = 'Пароли не совпадают';
					redirect();
				}
            }
			$user->attributes['newsletter'] = $user->attributes['newsletter'] ? '1' : '0';            
            if(!$user->validate($data) || !$user->checkUnique()){
                $user->getErrors();
                redirect();
            }
            if($user->update('user', $id)){
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','40','user','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
		$user_groups = \R::getAll("SELECT * FROM user_groups WHERE role != 'user'");
        $user_id = $this->getRequestID();
        $user = \R::load('user', $user_id);
		$newsletters = \R::getAll("SELECT * FROM user_newsletter, newsletter WHERE user_newsletter.newsletter_id = newsletter.id AND user_newsletter.user_id = '".$user_id."'");

        $this->setMeta('Редактирование профиля администратора');
        $this->set(compact('user', 'user_groups', 'newsletters'));
    }
	
	public function deleteCustomerAction(){
        $id = $this->getRequestID();
		$find = \R::findOne('user', 'id = ?',[$id]);
        $user = \R::load('user', $find->id);
		if($find->role == "admin") { $_SESSION['error'] = 'Нельзя удалить администратора';
		}else{		
			\R::trash($user);
			\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','41','user','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
			$_SESSION['success'] = 'Пользователь '.$user["email"].' удален';			
		}
		redirect();
    }
	
	public function useradminAction(){
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $data['items'] = [];
        $useradmin = \R::getAssoc('SELECT id, name FROM user WHERE name LIKE ? LIMIT 10', ["%{$q}%"]);
        if($useradmin){
            $i = 0;
            foreach($useradmin as $id => $name){
                $data['items'][$i]['id'] = $id;
                $data['items'][$i]['text'] = $name;
                $i++;
            }
        }
        echo json_encode($data);
        die;
    }
	
	public function editNewsletterAction(){
            $id = $this->getRequestID(false);
            $user = new \app\models\admin\User();
            $data = $_POST;
            $user->editNewsletterGroup($id, $data); 
			$_SESSION['success'] = 'Подписка обновлена';
            redirect();
    }
	
	public function addblockAction(){        			
		$id = $_GET['id'];
		$checked = $_GET['checked'];
		
		if($checked==1){
			\R::exec("UPDATE `user` SET newsletter='0' WHERE `id` = ?", [$id]);				
		}
		if($checked==0){
			\R::exec("UPDATE `user` SET newsletter='1' WHERE `id` = ?", [$id]);				
		}	
		
		if($this->isAjax()){
			require APP . "/views/" . TEMPLATE . "/admin/User/switch.php";
			die;
		}
    }
	
	public function contactsAction(){
        
		$q = isset($_GET['q']) ? $_GET['q'] : '';

        $data['items'] = [];
		if($q){
			$usercontact = \R::getAssoc('SELECT id, name, email FROM user WHERE role = ? AND concat(name, email) LIKE ? LIMIT 10', ['user', "%".rawurldecode($q)."%"]);
			if($usercontact){
				$i = 0;
				foreach($usercontact as $id => $k){
					$data['items'][$i]['id'] = $id;
					$data['items'][$i]['text'] = "".$k["name"]." (".$k["email"].")";
					$i++;
				}
			}
			echo json_encode($data);
			die;
		}
    }

}