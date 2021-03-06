<?php

class TestBelongsTo extends FuzzyTest {

	public function test_belongs_to() {
	
		MemoryStore::flush();
		Page::delete_all();
		User::delete_all();
		
		$user = new User();
		$user->email = "ben@allseeing-i.com";
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = new Date();
		$user->first_name = "Ben";
		$user->save();

		
		$page1 = new Page();
		$page1->title = "This is page 1";
		$page1->last_modified = new Date();
		$page1->body = "This is the content";
		$page1->url = "page-1";
		$page1->author = $user;
		$page1->save();
		FuzzyTest::assert_equal($page1->author_id,$user->id,"Author not set correctly");
		

		
		$user->delete();
		
		$page = Page::find_by_url('page-1');
		FuzzyTest::assert_true(isset($page),"Page deleted when it should have been preserved");
		
		FuzzyTest::assert_equal($page->author_id,0,"Page deleted when it should have been preserved");
		
		$user->save();
	
		$page->author = $user;
		$page->save();
	
		$matches = $user->pages;
		FuzzyTest::assert_equal(count($matches),1,"Page count should be 1");	

	}

}