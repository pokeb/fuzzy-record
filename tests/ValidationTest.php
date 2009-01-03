<?php

class ValidationTest extends FuzzyTest {
	
	public function test_validation() {
	
		MemoryStore::flush();
		UserLogin::delete_all();
		User::delete_all();
		
		$user = new User();
		FuzzyTest::assert_false($user->validate(),"User write should fail because required properties were not set");
		FuzzyTest::assert_true(key_exists("email",$user->validation_errors),"Email validation error not set");
		FuzzyTest::assert_true(key_exists("password",$user->validation_errors),"Password validation error not set");
		FuzzyTest::assert_true(key_exists("accepted_terms_and_conditions",$user->validation_errors),"Boolean validation error not set");
		FuzzyTest::assert_true(key_exists("registration_date",$user->validation_errors),"Required validation error not set");
		

		$user->accepted_terms_and_conditions = true;
		$user->validate();
		FuzzyTest::assert_false(key_exists("accepted_terms_and_conditions",$user->validation_errors),"Boolean validation error erroneously set");
		
		$user->email = "ben@allseeing-i.com";
		$user->validate();
		FuzzyTest::assert_false(key_exists("email",$user->validation_errors),"Email validation error erroneously set");	
		
		$user->password = "secret";
		$user->validate();
		FuzzyTest::assert_false(key_exists("password",$user->validation_errors),"Password validation error erroneously set");		
		
		$user->registration_date = DateHelper::now();
		$user->validate();
		FuzzyTest::assert_false(key_exists("registration_date",$user->validation_errors),"Required validation error erroneously set");
		
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		$user->validate();
		FuzzyTest::assert_false(key_exists("first_name",$user->validation_errors),"Length validation error erroneously set");
		FuzzyTest::assert_false(key_exists("last_name",$user->validation_errors),"Length validation error erroneously set");		
		
		FuzzyTest::assert_true($user->validate(),"Validate should succeed because required properties were set");
	
	}
}