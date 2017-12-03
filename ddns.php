<?php
	$config = array(
		'token'=>'12345,0123456789abcde0123456789abcde01',
		//到https://www.dnspod.cn/console/user/security创建Token，这里的Token包括ID和Token两部分，用逗号分隔
		'subdomain'=>'www',
		'domain'=>'cyyself.name'
	);
	function get_public_ip() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://ns1.dnspod.net:6666');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = trim(curl_exec($ch));
		$ip = array();
		sscanf($result,"%d.%d.%d.%d",$ip[0],$ip[1],$ip[2],$ip[3]);
		if ($ip[0] != 0 && $ip[1] != 0 && $ip[2] != 0 && $ip[3] != 0) {
			return $result;
		}
		else return false;
	}
	function dnspod_query($dn) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://119.29.29.29/d?dn=' . $dn);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		return trim(curl_exec($ch));
	}
	function curl_post($curl,$postdata) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $curl);
		curl_setopt($ch, CURLOPT_USERAGENT, "CYY DDNS Client/1.0.0 (cyy@cyyself.name)");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		return curl_exec($ch);
	}
	function update_record($token,$subdomain,$domain,$ip) {
		$data = array(
			'login_token'=>$token,
			'format'=>'json'
		);
		$result = curl_post('https://dnsapi.cn/Domain.List',$data);

		if ($result === false) {
			echo '错误1。请检查你的互联网连接。';
			exit;
		}
		$result = json_decode($result,true);
		if ($result['status']['code'] != '1') {
			echo '错误2。请检查你配置的Token。';
			exit;
		}
		$domainid = -1;
		foreach ($result['domains'] as $eachdomain) {
			if ($eachdomain['name'] == $domain) {
				$domainid = $eachdomain['id'];
				break;
			}
		}
		if ($domainid == -1) {
			echo '错误3。请检查你配置的域名。';
			exit;
		}

		$data = array(
			'login_token'=>$token,
			'format'=>'json',
			'domain_id'=>$domainid
		);
		$result = curl_post('https://dnsapi.cn/Record.List',$data);
		$result = json_decode($result,true);
		$recordid = -1;
		foreach ($result['records'] as $eachsubdomain) {
			if ($eachsubdomain['name'] == $subdomain) {
				$recordid = $eachsubdomain['id'];
				break;
			}
		}
		if ($recordid == -1) {
			echo '错误4。请检查你配置的子域名。';
			exit;
		}
		$data = array(
			'login_token'=>$token,
			'format'=>'json',
			'domain_id'=>$domainid,
			'record_id'=>$recordid,
			'sub_domain'=>$subdomain,
			'record_type'=>'A',
			'record_line'=>'默认',
			'value'=>$ip
		);
		$result = curl_post('https://dnsapi.cn/Record.Modify',$data);
		$result = json_decode($result,true);
		if ($result['status']['code'] == 1) {
			echo "更新记录成功！";
		}
		else {
			echo $result['status']['message'];
		}
	}
	$full_hostname = $config['subdomain'].'.'.$config['domain'];
	$public_ip =  get_public_ip();
	if ($public_ip === false) {
		echo "获取公网IP失败，请检查你的互联网连接或更换DNS API。\n";
		exit;
	}
	$current_record = gethostbyname($full_hostname);
	echo '公网IP:' . $public_ip . "\n";
	echo '记录IP:' . $current_record . "\n";
	if ($public_ip == $current_record) {//使用系统DNS查询结果和公网IP一致
		echo "无需更新!\n";
	}
	else {
		$current_record = dnspod_query($full_hostname);
		echo '记录IP:' . $current_record . "\n";
		if ($current_record == $public_ip) {//考虑系统使用的DNS缓存更新问题，使用DNSPOD HTTP DNS再次查询以确认需要更新
			echo "无需更新!\n";
		}
		else {
			update_record($config['token'],$config['subdomain'],$config['domain'],$public_ip);
		}
	}
?>
