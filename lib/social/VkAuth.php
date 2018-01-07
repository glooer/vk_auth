<?php

namespace Social;

class VkAuth {
	public static $user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";

  public static function auth($args) {

		if (empty($args['email'])) {
			throw new \Exception("Не передан email", 1);
		}

		if (empty($args['pass'])) {
			throw new \Exception("Не передан pass", 1);
		}

		// значения по умолчанию
		// могут работать не правильно, не проверял, возможно надо поменять местами массивы.
		$args = array_merge($args, [
			'scope' => 81054,
			'client_id' => 3998121
		]);


		$cookie_file = tempnam(sys_get_temp_dir(), 'vk_auth');

		// заходим на главную страницу, находим форму, и отправляем эту форму (всё что бы получить куки)
		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL => "https://vk.com",
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_USERAGENT => self::$user_agent,
			CURLOPT_COOKIEFILE => $cookie_file,
			CURLOPT_COOKIEJAR => $cookie_file,
		]);

		$res = curl_exec($ch);

		preg_match('/<form\b[^>]*>.*?<\/form>/si', $res, $form);

		$form = $form[0];

		preg_match('/<form.+action="([^"]+)"/', $form, $action);

		$action = $action[1];

		preg_match_all('/<input([^>]+)>/', $form, $inputs);

		$inputs = $inputs[1];
		$inputs = array_reduce($inputs, function($acc, $item) {
			preg_match('/name="([^"]+)"/', $item, $name);
			preg_match('/value="([^"]+)"/', $item, $value);

			if ($name[1]) {
				$acc[$name[1]] = $value[1];
			}

			return $acc;
		}, []);


		$inputs['email'] = $args['email'];
		$inputs['pass'] = $args['pass'];


		// авторизуемся
		curl_setopt_array($ch, [
			CURLOPT_URL => $action,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POST => 1,
			CURLOPT_USERAGENT => self::$user_agent,
			CURLOPT_COOKIEFILE => $cookie_file,
			CURLOPT_COOKIEJAR => $cookie_file,
			CURLOPT_POSTFIELDS => http_build_query($inputs),
		]);

		$res = curl_exec($ch);

		// прыгаем на страницу с получением токена
		curl_setopt_array($ch, [
			CURLOPT_URL => 'https://oauth.vk.com/authorize?client_id=' . $args['client_id'] . '&redirect_uri=https%3A%2F%2Foauth.vk.com%2Fblank.html+&scope=' . $args['scope'] . '&0=262144&1=524288&response_type=token&v=5.69',
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT => self::$user_agent,
			CURLOPT_COOKIEFILE => $cookie_file,
			CURLOPT_COOKIEJAR => $cookie_file,
			CURLOPT_HEADER => 1,
		]);

		$res = curl_exec($ch);

		preg_match_all('/Location: (.+)/', $res, $location);

		$location = array_pop($location)[1];

		preg_match('/access_token=([^&]+)/', $location, $access_token);

		$access_token = $access_token[1];

		if (!$access_token) { // если токена нет -- значит у нас попросили подтверждение, находим ссылку и переходим по ней
			preg_match('/location.href = "([^"]+)"/', $res, $test);

			curl_setopt($ch, CURLOPT_URL, $test[1]);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, self::$user_agent);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt($ch, CURLOPT_HEADER, 1);

			$res = curl_exec($ch);


			preg_match_all('/Location: (.+)/', $res, $location, PREG_SET_ORDER);
			$location = array_pop($location)[1];


			preg_match('/access_token=([^&]+)/', $location, $access_token);
			$access_token = $access_token[1];

		}

		curl_close($ch);

		return $access_token;
	}
}


?>
