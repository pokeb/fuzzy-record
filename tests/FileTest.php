<?php

class FileTest extends FuzzyTest {
	
	//Setup data for these tests
	public function __construct() {
	
		MemoryStore::flush();		
		Document::delete_all();
		CustomDocument::delete_all();
		User::delete_all();
		
		$text = "This is the content of the file";
		$file = new File();
		$file->contents = $text;
		$file->write("/tmp/test.txt");
		
		$user = new User();
		$user->email = "ben@allseeing-i.com";
		$user->password = "secret";
		$user->accepted_terms_and_conditions = true;
		$user->registration_date = new Date();
		$user->first_name = "Ben";
		$user->last_name = "Copsey";
		$user->save();
	
	}
	
	public function test_mime_type() {
		$file = new File("/tmp/test.txt");
		$mime = $file->mime_type();
		FuzzyTest::assert_equal($mime,"text/plain","Wrong mime type");
	}
	
	public function test_contents() {
		$file = new File("/tmp/test.txt");
		FuzzyTest::assert_equal($file->read(),"This is the content of the file","Contents were not read");
	}
	
	public function test_basic_write() {
	
		$file = new File("/tmp/test.txt");
		FuzzyTest::assert_true($file->exists(),"File not found");
		
		$save_path = DOCUMENT_SAVE_PATH."/test.txt";
		
		$file->write($save_path);
		FuzzyTest::assert_true($file->exists(),"File not written");
		
		$contents = $file->read();
		FuzzyTest::assert_equal($file->read(),"This is the content of the file","Contents were not read");
		
		$file->delete();
		FuzzyTest::assert_false($file->exists(),"File not deleted");
		
	}
	
	public function test_file_field_read_and_write() {
		
		$file = new File("/tmp/test.txt");
		
		$user = User::find_by_email("ben@allseeing-i.com");
		$document = new Document();
		$document->user = $user;
		$document->last_modified = new Date();
		$document->file = $file;
		$document->save();
		FuzzyTest::assert_true($file->exists(),"File not written");
			
		$document = Document::find_by_id($document->id);
		$result = is_a($document->file,"File");
		FuzzyTest::assert_true($result,"File not read correctly");
		
		$contents = $document->file->read();
		FuzzyTest::assert_equal($contents,"This is the content of the file","File not read correctly");
		
	}
	
	public function test_custom_file_name() {
		$file = new File("/tmp/test.txt");
		
		$result = file_exists($file->path);
		FuzzyTest::assert_true($result,"File not written");
		
		$user = User::find_by_email("ben@allseeing-i.com");
		$document = new CustomDocument();
		$document->user = $user;
		$document->last_modified = new Date();
		$document->file = $file;
		$document->save();
		$result = file_exists(DOCUMENT_SAVE_PATH."/".$document->id.".info");
		FuzzyTest::assert_true($result,"File not written");	
	}
}