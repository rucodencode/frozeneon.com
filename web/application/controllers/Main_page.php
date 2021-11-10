<?php

use Model\Boosterpack_model;
use Model\Post_model;
use Model\User_model;
use Model\Comment_model;
use Model\Login_model;
use Codencode\Transaction_info;
use Model\Analytics_model;

/**
 * Created by PhpStorm.
 * User: mr.incognito
 * Date: 10.11.2018
 * Time: 21:36
 */
class Main_page extends MY_Controller
{

    public function __construct()
    {

        parent::__construct();

        if (is_prod())
        {
            die('In production it will be hard to debug! Run as development environment!');
        }
    }

    public function index()
    {
        $user = User_model::get_user();

        App::get_ci()->load->view('main_page', ['user' => User_model::preparation($user, 'default')]);
    }

    public function get_all_posts()
    {
        $posts =  Post_model::preparation_many(Post_model::get_all(), 'default');
        return $this->response_success(['posts' => $posts]);
    }

    public function get_boosterpacks()
    {
        $posts =  Boosterpack_model::preparation_many(Boosterpack_model::get_all(), 'default');
        return $this->response_success(['boosterpacks' => $posts]);
    }

    public function login()
    {
        // TODO: task 1, аутентификация+++

		$login = trim(strip_tags(App::get_ci()->input->post('login')));
		$password = trim(strip_tags(App::get_ci()->input->post('password')));

		if(empty($login) || empty($password))
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS);


		try {
			$user = User_model::find_user_by_email($login);

			if ($user->is_loaded() && $user->get_password() === $password){
				Login_model::login($user->get_id());
				return $this->response_success(['user' => User_model::preparation($user, 'default')]);
			}
		} catch (Exception $exception){
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, $exception->getMessage());
		}

		return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS);
    }

    public function logout()
    {
        // TODO: task 1, аутентификация+++

		Login_model::logout();
    }

    public function comment()
    {
        // TODO: task 2, комментирование+++

		if ( ! User_model::is_logged())
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);

		$user_id = User_model::get_user()->get_id();
		$assign_id = abs(intval(App::get_ci()->input->post('postId')));
		$text = trim(strip_tags(App::get_ci()->input->post('commentText')));
		$reply_id = abs(intval(App::get_ci()->input->post('reply_id'))) ?: NULL;

		if(empty($assign_id) || empty($text))
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS);

		try{

			//простая проверка, выкинет исключение, если не найдёт
			if(!is_null($reply_id)){
				$reply = new Comment_model($reply_id);
			}

			$comment = Comment_model::create(['user_id' => $user_id, 'assign_id' => $assign_id, 'text' => $text, 'reply_id' => $reply_id]);
			return $this->response_success(['comment' => Comment_model::preparation($comment, 'default')]);
		} catch (Exception $exception){
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, $exception->getMessage());
		}

    }

    public function like_comment(int $comment_id)
    {
        // TODO: task 3, лайк комментария+++

		if ( ! User_model::is_logged())
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);

		$comment_id = abs($comment_id);
		if(empty($comment_id))
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS);

		try {
			$user = User_model::get_user();
			$comment = new Comment_model($comment_id);
			if($comment->increment_likes($user)){
				return $this->response_success(['likes' => $comment->get_likes()]);
			} else {
				return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_DISABLED);
			}
		} catch (Exception $exception){
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, $exception->getMessage());
		}
    }

    public function like_post(int $post_id)
    {
        // TODO: task 3, лайк поста+++

		if ( ! User_model::is_logged())
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);

		$post_id = abs($post_id);
		if(empty($post_id))
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS);

		try {
			$user = User_model::get_user();
			$post = new Post_model($post_id);
			if($post->increment_likes($user)){
				return $this->response_success(['likes' => $post->get_likes()]);
			} else {
				return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_DISABLED);
			}
		} catch (Exception $exception){
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, $exception->getMessage());
		}
    }

    public function add_money()
    {
        // TODO: task 4, пополнение баланса+++

        $sum = (float)App::get_ci()->input->post('sum');
		$sum = abs($sum);

		// Check user is authorize
		if ( ! User_model::is_logged())
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);

		if(empty($sum))
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS);

		$user = User_model::get_user();
		$initiator = ['object' => 'user'];

		if($user->add_money($sum, $initiator)){
			return $this->response_success(['user' => User_model::preparation($user, 'default')]);
		} else {
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_TRY_LATER);
		}

    }

    public function get_post(int $post_id) {
        // TODO получения поста по id+++

		$post_id = abs($post_id);
		if(empty($post_id))
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS);

		try {
			$post = new Post_model($post_id);
			return $this->response_success(['post' => Post_model::preparation($post, 'full_info')]);
		} catch (Exception $exception){
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, $exception->getMessage());
		}
    }

    public function buy_boosterpack()
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);

        // TODO: task 5, покупка и открытие бустерпака
		$id = abs(intval(App::get_ci()->input->post('id')));

		if(empty($id))
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS);

		$user = User_model::get_user();

		$boosterpack = new Boosterpack_model($id);
		$initiator = ['object' => Transaction_info::OBJECT_BOOSTER_PACK, 'object_id' => $id];
		if($user->remove_money($boosterpack->get_price(), $initiator)){
			$amount = $boosterpack->open();
			$user->set_likes_balance($user->get_likes_balance() + $amount);
			return $this->response_success(['amount' => $amount]);
		} else {
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_TRY_LATER);
		}
    }





    /**
     * @return object|string|void
     */
    public function get_boosterpack_info(int $bootserpack_info)
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }


        //TODO получить содержимое бустерпака
    }

	public function get_history_wallet() {
		if ( ! User_model::is_logged())
			return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);

		$analytic = new Analytics_model();

		$records = $analytic->get_analytics_for_user(User_model::get_user()->get_id());
		return $this->response_success(['records' => array_reverse($records)]);
	}
}
