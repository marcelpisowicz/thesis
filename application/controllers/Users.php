<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends Auth_Controller
{

    public function index()
    {
        $users = User::all();
        $this->table->add_column(_('Nazwa użytkownika') ,'username');
        $this->table->add_column(_('Email') ,'email');
        $this->table->add_column(_('Ostatnie logowanie') ,'last_login');
        $this->table->add_column(_('Aktywny') ,'active', true);

        $this->table->add_click();

        $this->add_menu_new();

        $this->data['table'] = $this->table->generate($users);

        $this->render('users/index_view');
    }

    public function details($id = null)
    {
        $user = User::findOrNew($id);
        $current_user = User::find($this->session->userdata('user_id'));

        $this->model($user);
        $this->data['id'] = $id;

        $this->add_menu_return();
        $this->add_menu_save();
        if(empty($user->admin) && ($current_user->id !== $user->id) && !empty($current_user->admin)) {
            $this->add_menu_delete($id);
        }

        $this->render('users/details_view');
    }

    public function save()
    {
        $post = $this->input->post();
        $user_id = (int)$post['id'];
        $user = User::findOrNew($user_id);

        $user_id = $user->save_user($post);

        $this->redirect($user_id);
    }

    public function delete($id)
    {
        $user = User::findOrNew($id);
        $current_user = User::find($this->session->userdata('user_id'));

        if(empty($user->admin) && ($current_user->id !== $user->id) && !empty($current_user->admin)) {
            $user->delete();
        }

        $this->redirect();
    }
}