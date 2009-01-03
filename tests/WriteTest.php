<?php

class WriteTest extends FuzzyTest {

	public function test_simple_write() {
	
		MemoryStore::flush();
		UserLogin::delete_all();
		User::delete_all();
	
		$user = new User();
		$user->email = "ben@allseeing-i.com";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = "2008-12-12";
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		FuzzyTest::assert_true($user->write_new(),"Write should succeed because required properties were set");
		
		$user = new User();
		$user->email = "ben@allseeing-i.com";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = DateHelper::now();
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		FuzzyTest::assert_false($user->write_new(),"Write should fail because we have a duplicate for a unique property");	
	
		$user = new User();
		$user->email = "ben@acknet.co.uk";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = DateHelper::now();
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		FuzzyTest::assert_true($user->save(),"Save should succeed as a write_new");	
	}

}