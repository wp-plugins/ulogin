<?php
/*
Plugin Name: uLogin
Plugin URI: http://ulogin.ru/wordpress/
Description: uLogin
Version: 1.1
Author: uLogin
Author URI: http://ulogin.ru/
License: GPL2
*/

add_action('comment_form', ulogin_comment_form);
add_action('parse_request', ulogin_parse_request);
add_filter('get_avatar', ulogin_get_avatar);
function ulogin_comment_form() {
	global $current_user;
	if ($current_user->ID == 0) {
		?>
		<script type="text/javascript">
			(function() {
				var form = document.getElementById('commentform');
				if (form) {
					var div = document.createElement('div');
					div.innerHTML = '<div style="float:left;line-height:24px">Войти с помощью:&nbsp;</div><div id="uLogin" style="float:left"></div><div style="clear:both"></div>';
					form.parentNode.insertBefore(div, form);
					var s = document.createElement('script');
					s.src = 'http://ulogin.ru/js/widget.js?display=small&fields=first_name,last_name,email,photo&providers=vkontakte,odnoklassniki,mailru,facebook,twitter,google,yandex,livejournal,openid&hidden=twitter,google,yandex,livejournal,openid&redirect_uri=' + encodeURIComponent((location.href.indexOf('#') != -1 ? location.href.substr(0, location.href.indexOf('#')) : location.href) + '#commentform');
					document.body.appendChild(s);
				}
			})();
		</script>
		<?php
	}
}
function ulogin_panel() {
	global $current_user;
	if ($current_user->ID == 0) {
		echo '<div><div style="float:left;line-height:24px">Войти с помощью:&nbsp;</div><div id="uLogin" style="float:left"></div><div style="clear:both"></div></div>' . 
		'<script type="text/javascript">' .
		'var s = document.createElement(\'script\');' . 
		's.src = \'http://ulogin.ru/js/widget.js?display=small&fields=first_name,last_name,email,photo&providers=vkontakte,odnoklassniki,mailru,facebook,twitter,google,yandex,livejournal,openid&hidden=twitter,google,yandex,livejournal,openid&redirect_uri=\' + encodeURIComponent((location.href.indexOf(\'#\') != -1 ? location.href.substr(0, location.href.indexOf(\'#\')) : location.href) + \'#commentform\');' . 
		'document.body.appendChild(s);' . 
		'</script>';
	}
}
function ulogin_parse_request() {
	if (isset($_POST['token'])) {
		$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
		$user = json_decode($s, true);
		$user_id = get_user_by('login', 'ulogin_' . $user['network'] . '_' . $user['uid']);
		if (isset($user_id->ID)) {
			$user_id = $user_id->ID;
		} else {
			$user_id = wp_insert_user(array('user_pass' => md5(microtime()), 'user_login' => 'ulogin_' . $user['network'] . '_' . $user['uid'], 'user_url' => $user['identity'], 'user_email' => $user['email'], 'first_name' => $user['first_name'], 'last_name' => $user['last_name'], 'display_name' => $user['first_name'] . ' ' . $user['last_name'], 'nickname' => $user['first_name'] . ' ' . $user['last_name']));
		}
		update_usermeta($user_id, 'ulogin_photo', $user['photo']);
		wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
	}
}
function ulogin_get_avatar($text) {
	global $comment;
	$user = get_userdata($comment->user_id);
	$network = $user->user_login;
	if (strpos($network, 'ulogin_') !== false) {
		$photo = get_usermeta($comment->user_id, 'ulogin_photo');
		return preg_replace('/src=([^\s]+)/i', 'src="' . $photo . '"', $text);
	} else return $text;
}