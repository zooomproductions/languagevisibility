<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Kasper Ligaard (ligaard@daimi.au.dk)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case for checking the PHPUnit 3.1.9
 *
 * WARNING: Never ever run a unit test like this on a live site!
 *
 *
 * @author	Tolleiv Nietsch
 */

require_once(t3lib_extMgm::extPath("languagevisibility").'classes/class.tx_languagevisibility_language.php');

// require_once (t3lib_extMgm::extPath('phpunit').'class.tx_phpunit_test.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');

class tx_visibilityServiceDB_testcase extends tx_phpunit_database_testcase {

	function test_visibility_ce() {
		$language = $this->_getLang(1);
		$visibility=t3lib_div::makeInstance('tx_languagevisibility_visibilityService');

		$fixturesWithoutOverlay = array('tt_content'=>1,'pages'=>1);
		foreach($fixturesWithoutOverlay as $table=>$uid) {
			$element = $this->_getContent($table,$uid);
			$this->assertEquals('-', $element->getLocalVisibilitySetting(1), "setting d expected");
			$this->assertEquals('f', $visibility->getVisibilitySetting($language,$element), "setting f expected (because default is used)");
			$this->assertEquals(true, $visibility->isVisible($language,$element), "default lang should be visible");
			$this->assertEquals(0, $visibility->getOverlayLanguageIdForLanguageAndElement($language,$element), sprintf("default should be overlay table:%s uid:%d",$table,$uid));
		}
	}
	function test_visibility_overlayCe() {
		$element = $this->_getContent('tt_content',2 /* element with L1 overlay */);
		$visibility=t3lib_div::makeInstance('tx_languagevisibility_visibilityService');

		$expectedResults=array(1=>1,2=>1,3=>0,4=>1);
		foreach($expectedResults as $langUid=>$expectedResult) {
			$language = $this->_getLang($langUid);

			$this->assertEquals(true, $visibility->isVisible($language,$element), "element should be visible in lang ".$expectedResult);
			$this->assertEquals($expectedResult, $visibility->getOverlayLanguageIdForLanguageAndElement($language,$element), sprintf("Element Overlay used wrong fallback - language %d - should be %d ",$langUid,$expectedResult));
		}
	}

	function test_visibility_overlayPage() {
		$language = $this->_getLang(1);
		$element = $this->_getContent('pages','2');
		$visibility=t3lib_div::makeInstance('tx_languagevisibility_visibilityService');

		$this->assertEquals(true, $visibility->isVisible($language,$element), "page should be visible");
		$this->assertEquals(1, $visibility->getOverlayLanguageIdForLanguageAndElement($language,$element), "Page-Overlay should be defined for lang 1 ...");
	}

	function test_visibility_complexOverlay() {
		$language = $this->_getLang(3);
		$visibility=t3lib_div::makeInstance('tx_languagevisibility_visibilityService');
		$fixtures = array(	'tt_content'	=>array('uid'=>2,'result'=>0),
						 	'pages'		=>array('uid'=>2,'result'=>1)
						 );
		foreach($fixtures as $table=>$tableFixtures) {
			$element = $this->_getContent($table,$tableFixtures['uid']);
			$this->assertEquals($tableFixtures['result'], $visibility->getOverlayLanguageIdForLanguageAndElement($language,$element), sprintf("Element Overlay used wrong fallback - language 2  table %s:%d- should be %d ",$table,$tableFixtures['uid'],$tableFixtures['result']));
		}
	}

	/**
	 * As discussed in issue 6863 an editor should be able to set the languagevisibility right
	 * "force to no" in the overlay record. 
	 *
	 * @param void
	 * @return void
	 * @see tx_languagevisibility_visibilityService
	 * @author Timo Schmidt
	 */
	function test_visibility_ttcontentOverlayForceToNoAffectsVisibility(){
		$language = $this->_getLang(1);
		
		/**
		 * The xml structure is used to to create a fixture tt_content element 
		 * with the visibility "yes" for all languages. For the same element
		 * an overlay in language 1 exists with the setting "force to no".
		 * In this case the "force to no" setting in the overlay should overwrite
		 * the "yes" setting in the content element. Therefore the element should not be
		 * visible. 
		 */
		
		$element 			= $this->_getContent('tt_content',4);
		
		//set visibility to true to ensure it is set to false by the service call
		$visibilityResult 	= true;
		
		$service			= new tx_languagevisibility_visibilityService();	
		$visibilityResult 	= $service->isVisible($language,$element);
		
		$this->assertFalse($visibilityResult,'tt-content element is visible, but should not be visible');
	}
	
	/**
	 * This testcase does exactly the same as the previos testcase (test_visibility_ttcontentOverlayForceToNoAffectsVisibility)
	 * but uses page elements.
	 * 
	 * @param void
	 * @return void
	 * @see tx_languagevisibility_visibilityService
	 * @return 
	 */
	function test_visibility_pagesOverlayForceToNoAffectsVisibility(){
		$language 			= $this->_getLang(1);	
		$element			= $this->_getContent('pages',4);
		$visibilityResult	= true;
		$service			= new tx_languagevisibility_visibilityService();	

		$visibilityResult 	= $service->isVisible($language,$element);
		
		$this->assertFalse($visibilityResult,'page element is visible, but should not be visible');
	}
	
	/**
	 * This testcase is used to test if an "force to no"-setting  in an overlay record in the workspace
	 * affects the original element in the workspace.
	 *  
	 * @return 
	 */
	function test_visibility_ttcontentOverlayForceToNoAffectsVisibilityAlsoInWorkspaces(){
		$this->_fakeWorkspaceContext(4711);
		
		$language 			= $this->_getLang(1);	
		$element			= $this->_getContent('tt_content',8);
		$visibilityResult	= true;
		
		$service			= new tx_languagevisibility_visibilityService();	

		$visibilityResult 	= $service->isVisible($language,$element);
		
		$this->assertFalse($visibilityResult,'page element is visible, but should not be visible');
	}
	
	
	/**
	 * The visibility setting in an overlay should only overwrite the visibility 
	 * when it is set to "force to no" a "force to yes" setting should not affect the orginal record.
	 *
	 */
	function test_visibility_ttcontentOverlayForceToYesNotAffectsVisibility(){
		$language = $this->_getLang(1);
		$element 			= $this->_getContent('tt_content',10);
		
		//set visibility to true to ensure it is set to false by the service call
		$visibilityResult 	= true;
		
		$service			= new tx_languagevisibility_visibilityService();	
		$visibilityResult 	= $service->isVisible($language,$element);	

		$this->assertFalse($visibilityResult,'visibility setting in overlay makes orginal element visible');
	}
	
	function test_visibility_ttcontentOverlayCorruptedNotAffectsVisibilits(){
		$language = $this->_getLang(1);
		$element 			= $this->_getContent('tt_content',12);
		
		//set visibility to true to ensure it is set to false by the service call
		$visibilityResult 	= false;
		
		$service			= new tx_languagevisibility_visibilityService();	
		$visibilityResult 	= $service->isVisible($language,$element);	

		$this->assertTrue($visibilityResult,'corrupted element forces visibility to no');
		
	}

	function test_visibility_ttcontentHasTranslationInAnyWorkspace(){
		$element 			= $this->_getContent('tt_content',14);
		$hasTranslation 	= true;
		$hasTranslation		= $element->hasAnyTranslationInAnyWorkspace();
		
		$this->assertFalse($hasTranslation,'Element without translation is determined as element with translation.');
	}
	
	function _loadWorkspaces(){
		$this->importDataSet(dirname(__FILE__). '/fixtures/dbDefaultWorkspaces.xml');
	}
	
	function _fakeWorkspaceContext($uid){
		$GLOBALS['BE_USER']->workspace = $uid;
	}
	
	function _getLang($uid) {
		if(!$this->_langImport) {
			$this->_langImport=true;
			$this->importDataSet(dirname(__FILE__). '/fixtures/dbDefaultLangs.xml');
		}
		$languageRep=t3lib_div::makeInstance('tx_languagevisibility_languagerepository');
		return $languageRep->getLanguageById($uid);
	}

	function _getContent($table,$uid) {
		if(!$this->_ceImport) {
			$this->_ceImport=true;
			$this->importDataSet(dirname(__FILE__). '/fixtures/dbContentWithVisibilityTestdata.xml');
		}
		$dao=new tx_languagevisibility_daocommon;
		$factoryClass=t3lib_div::makeInstanceClassName('tx_languagevisibility_elementFactory');
		
		$factory=new $factoryClass($dao);
		return $factory->getElementForTable($table,$uid);
	}

	function setUp() {
		$this->createDatabase();
		$db = $this->useTestDatabase();
		
		// order of extension-loading is important !!!!
		$this->importExtensions(array('corefake','cms','languagevisibility'));
		$this->_loadWorkspaces();
	}

	function tearDown() {
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
	}

}