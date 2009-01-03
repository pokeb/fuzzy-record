<?php

class TestFind extends FuzzyTest {
	
	//Setup data for these tests
	public function __construct() {
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
		$user->save();
	
		$user = new User();
		$user->email = "ben@acknet.co.uk";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = DateHelper::now();
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		$user->save();
		
		$user = new User();
		$user->email = "ben@shared-space.net";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = DateHelper::now();
		$user->first_name = "Benjamus";
		$user->last_name = "Copso";
		$user->save();	
		
		Page::delete_all();
		
		$page = new Page();
		$page->title = "test page";
		$page->url = "test-page";
		$page->body = "thing";
		$page->save();

		$page = new Page();
		$page->title = "test page 2";
		$page->url = "test-page-2";
		$page->body = "thing 2";
		$page->save();
		
		$page = new Page();
		$page->title = "test page 3";
		$page->url = "test-page-3";
		$page->body = "thing 3";
		$page->save();
	}

	public function test_greater_less_search() {
	
		$page = Page::find_by_position(1);
		
		$matches = Page::find(array('position_less_than'=>2));
		FuzzyTest::assert_equal(count($matches),1,"Should find one page here");
		
		$matches = Page::find_all_by_position_less_than(2);
		FuzzyTest::assert_equal(count($matches),1,"Should find one page here");		
		
		$matches = Page::find(array('position_greater_than'=>2));
		FuzzyTest::assert_equal(count($matches),1,"Should find one page here");	
		
		$matches = Page::find_all_by_position_greater_than(2);
		FuzzyTest::assert_equal(count($matches),1,"Should find one page here");	

		$matches = Page::find_all_by_position_greater_than_and_position_less_than(1,3);
		FuzzyTest::assert_equal(count($matches),1,"Should find one page here");	
		
	}

	public function test_like_search() {
		
		$matches = User::find_all_by_email_like('ben%');
		FuzzyTest::assert_equal(count($matches),3,"Should find three users here");
		
		$matches = User::find_all_by_email_like('%@%');
		FuzzyTest::assert_equal(count($matches),3,"Should find three users here");
		
		$matches = User::find_all_by_email_like('nat%');
		FuzzyTest::assert_equal(count($matches),0,"Should find no users here");	

		$matches = User::find_all_by_email_not_like('nat%');
		FuzzyTest::assert_equal(count($matches),3,"Should find three users here");

		$matches = User::find_all_by_email_not_like('ben%');
		FuzzyTest::assert_equal(count($matches),0,"Should find no users here");		
		
		$matches = User::find_all_by_email_like_and_email_not_like('ben@%','%.net');
		FuzzyTest::assert_equal(count($matches),2,"Should find two users here");
		
		$matches = User::find(array("like" => "ben%"));
		FuzzyTest::assert_equal(count($matches),3,"Should find three users here");

		$matches = User::find(array("like" => "Cops%"));
		FuzzyTest::assert_equal(count($matches),3,"Should find three users here");
		
		$matches = User::find(array("like" => "Copso%"));
		FuzzyTest::assert_equal(count($matches),1,"Should find one user here");
	}

	public function test_basic_find() {

		$count = User::count();
		FuzzyTest::assert_equal($count,3,"Should find three users here");

		$matches = User::find_all();
		FuzzyTest::assert_equal(count($matches),3,"Should find three users here");
		
		$count = User::count(array('email'=>'ben@allseeing-i.com'));
		FuzzyTest::assert_equal($count,1,"Should find one user here");
	
		$matches = User::find(array('email'=>'ben@allseeing-i.com'));
		FuzzyTest::assert_equal(count($matches),1,"Should find one user here");

		$u = $matches[0];
		FuzzyTest::assert_equal($u->email,"ben@allseeing-i.com","Found wrong user");
		
		$count = User::count(array('first_name'=>'Ben'));
		FuzzyTest::assert_equal($count,2,"Should find two users here");

		$matches = User::find(array('first_name'=>'Ben'));
		FuzzyTest::assert_equal(count($matches),2,"Should find two users here");
		
		$matches = User::find_all_by_first_name('Ben');
		FuzzyTest::assert_equal(count($matches),2,"Should find two users here");
		
		$count = User::count_by_first_name('Ben');
		FuzzyTest::assert_equal($count,2,"Should find two users here");		
		
		$matches = User::find_all_by_email('ben@allseeing-i.com');
		FuzzyTest::assert_equal(count($matches),1,"Should find one user here");

		$count = User::count_by_email('ben@allseeing-i.com');
		FuzzyTest::assert_equal($count,1,"Should find one user here");
		
		$u = $matches[0];
		FuzzyTest::assert_equal($u->email,"ben@allseeing-i.com","Found wrong user");
	
		$u = User::find_by_email('ben@allseeing-i.com');
		FuzzyTest::assert_equal($u->email,"ben@allseeing-i.com","Found wrong user");
		
		$matches = User::find_all_by_email_and_first_name('ben@allseeing-i.com','Ben');
		FuzzyTest::assert_equal(count($matches),1,"Should find one user here");
		
		$count = User::count_by_email_and_first_name('ben@allseeing-i.com','Ben');
		FuzzyTest::assert_equal($count,1,"Should find one user here");
		
		$u = $matches[0];
		FuzzyTest::assert_equal($u->email,"ben@allseeing-i.com","Found wrong user");
		
		$matches = User::find(array('first_name'=>'Ben','limit'=>1));
		FuzzyTest::assert_equal(count($matches),1,"Should find one user here");
	
		$matches = User::find(array('first_name'=>'Ben','order_by'=>'registration_date'));
		FuzzyTest::assert_equal(count($matches),2,"Should find two users here");
		
		$u = $matches[0];
		FuzzyTest::assert_equal($u->email,"ben@allseeing-i.com","Sorted results in the wrong order");
	
		$matches = User::find(array('first_name'=>'Ben','order_by'=>'email', 'sort'=>"descending"));
		FuzzyTest::assert_equal(count($matches),2,"Should find two users here");
		
		$u = $matches[0];
		FuzzyTest::assert_equal($u->email,"ben@allseeing-i.com","Sorted results in the wrong order");
		
		$matches = User::find(array('first_name'=>'Ben','order_by'=>'email', 'sort'=>"ascending"));
		FuzzyTest::assert_equal(count($matches),2,"Should find two users here");
		
		$u = $matches[1];
		FuzzyTest::assert_equal($u->email,"ben@allseeing-i.com","Found wrong user");
		
		$matches = User::find(array('email_not'=>'ben@allseeing-i.com'));
		FuzzyTest::assert_equal(count($matches),2,"Should find two users here");

		
	}

}