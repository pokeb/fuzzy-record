<?php

class TestHasMany extends FuzzyTest {

	public function test_has_many() {
	
		UserLogin::delete_all();
		User::delete_all();
		
		$user = new User();
		$user->email = "ben@allseeing-i.com";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = "2008-12-12";
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		$user->save();		

		$user = new User();
		$user->email = "ben@acknet.co.uk";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = DateHelper::now();
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		$user->save();
		
		$user = User::find_by_email('ben@allseeing-i.com');
	
		$user_login = new UserLogin();
		$user_login->user_id = $user->id;
		$user_login->date = DateHelper::now();
		$user_login->ip_address = "127.0.0.1";
		$user_login->save();
		
		
	
		$matches = $user->logins();
		FuzzyTest::assert_equal(count($matches),1,"Should find one login here");
		
		$user_login = new UserLogin();
		$user_login->user_id = $user->id;
		$user_login->date = DateHelper::now();
		$user_login->ip_address = "127.0.0.1";
		$user_login->save();
		
		$matches = UserLogin::find_all_by_user($user);
		FuzzyTest::assert_equal(count($matches),2,"Should find two logins here");
		
		$matches = UserLogin::find_all_where("user_id = ".DB::escape($user->id));
		FuzzyTest::assert_equal(count($matches),2,"Should find two logins here");
	
		$matches = UserLogin::find_all_where("user_id = ".DB::escape($user->id),1);
		FuzzyTest::assert_equal(count($matches),1,"Should find one login here");;
		
		$login = $matches[0];
	
		$matches = UserLogin::find_all_where("user_id = ".DB::escape($user->id),2);
		$login2 = $matches[0];
		FuzzyTest::assert_equal($login->id,$login2->id,"Two logins should be equal");
	
		$matches = UserLogin::find_all_where("user_id = ".DB::escape($user->id),0,2);
		$login2 = $matches[0];
		FuzzyTest::assert_equal($login->id,$login2->id,"Two logins should be equal");
	
		$matches = UserLogin::find_all_where("user_id = ".DB::escape($user->id),1,2);
		$login2 = $matches[0];
		FuzzyTest::assert_not_equal($login->id,$login2->id,"Two logins should be equal");
		
		$matches = $user->logins();
		FuzzyTest::assert_equal(count($matches),2,"Should find two logins here");
		
		$login = UserLogin::find_by_user_id($user->id);
		FuzzyTest::assert_equal($login->user_id,$user->id,"Found wrong login");
	
		$login = UserLogin::find_by_user($user);
		FuzzyTest::assert_equal($login->user_id,$user->id,"Found wrong login");

		$u = User::find_by_login($user_login);
		FuzzyTest::assert_equal($u->id,$user->id,"Found wrong user");
		
		$user_login = new UserLogin();
		$user_login->user = $user;
		$user_login->date = DateHelper::now();
		$user_login->ip_address = "127.0.0.1";
		$user_login->save();
		
		$matches = $user->logins();
		FuzzyTest::assert_equal(count($matches),3,"Should find three logins here");
		
		$user->delete();
	
		$matches = UserLogin::find_by_user_id($user->id);
		FuzzyTest::assert_equal(count($matches),0,"Should find zero logins here");
	
		$matches = UserLogin::find_by_user($user);
		FuzzyTest::assert_equal(count($matches),0,"Should find zero logins here");
	
		UserLogin::delete_all();
		
		$user_login1 = new UserLogin();
		$user_login1->date = DateHelper::now();
		$user_login1->ip_address = "127.0.0.1";
		
		$user_login2 = new UserLogin();
		$user_login2->date = DateHelper::now();
		$user_login2->ip_address = "127.0.0.1";
		

		//$user->save();
		
		$user->logins = array($user_login1,$user_login2);
		$user->save();
	
		$matches = $user->logins;

		FuzzyTest::assert_equal(count($matches),2,"Should find two logins here");

		
		$user2 = new User();
		$user2 = User::find_by_email('ben@acknet.co.uk');
		$user_login1->user = $user2;
		$user_login1->save();
		
		$matches = $user->logins;
		FuzzyTest::assert_equal(count($matches),1,"Should find one login here");
		
		$matches = $user2->logins();
		FuzzyTest::assert_equal(count($matches),1,"Should find one login here");
	}
}