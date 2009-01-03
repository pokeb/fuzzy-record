<?php

class SorterTest extends FuzzyTest {
	
	//Setup data for these tests
	public function __construct() {
	
		MemoryStore::flush();		
		Page::delete_all();
		
		$page1 = new Page();
		$page1->title = "This is page 1";
		$page1->last_modified = DateHelper::now();
		$page1->body = "This is the content";
		$page1->url = "page-1";
		$page1->save();
	
		$page2 = new Page();
		$page2->title = "This is page 2";
		$page2->last_modified = DateHelper::now();
		$page2->body = "This is the content for page 2";
		$page2->url = "page-2";
		$page2->save();
	
	
		$page3 = new Page();
		$page3->title = "This is page 3";
		$page3->last_modified = DateHelper::now();
		$page3->body = "This is the content for page 3";
		$page3->url = "page-3";
		$page3->save();	
		
	}
	
	public function test_sorter() {
		
		$page = Page::find_by_url("page-2");
		FuzzyTest::assert_equal($page->position,2,"Page was created in the wrong position");
		
		$page->move_down('position');
		FuzzyTest::assert_equal($page->position,3,"Page was moved to the wrong position");
		
		$page->move_up('position');
		FuzzyTest::assert_equal($page->position,2,"Page was moved to the wrong position");
		
		$page->move_up('position');
		FuzzyTest::assert_equal($page->position,1,"Page was moved to the wrong position");
	
		$page = Page::find_by_url("page-3");
		FuzzyTest::assert_equal($page->position,2,"Page was moved to the wrong position");
	
		$page = Page::find_by_url("page-1");
		FuzzyTest::assert_equal($page->position,3,"Page was moved to the wrong position");
	}

}