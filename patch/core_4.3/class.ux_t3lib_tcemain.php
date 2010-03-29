<?php

class ux_t3lib_TCEmain extends t3lib_TCEmain	{

	/**
	 * Used to evaluate if a page can be deleted
	 *
	 * @param	integer		Page id
	 * @return	mixed		If array: List of page uids to traverse and delete (means OK), if string: error code.
	 */
	function canDeletePage($uid)	{
		$return = parent::canDeletePage($uid);
		if (is_array($return)) {
			if (t3lib_extMgm::isLoaded('languagevisibility')) {
					require_once(t3lib_extMgm::extPath("languagevisibility").'class.tx_languagevisibility_beservices.php');
					$visibilityservice=t3lib_div::makeInstance('tx_languagevisibility_beservices');
					if (!$visibilityservice->hasUserAccessToPageRecord($uid,'delete')) {
						return 'Attempt to delete records without access to the visible languages';
					}
				}
		}
		return $return;
	}

	/**
	 * Checks if user may update a record with uid=$id from $table
	 *
	 * @param	string		Record table
	 * @param	integer		Record UID
	 * @param	array		Record data
	 * @param	array		Hook objects
	 * @return	boolean		Returns true if the user may update the record given by $table and $id
	 */
	function checkRecordUpdateAccess($table, $id, $data=false, &$hookObjectsArr=false)	{
		global $TCA;
		/**
		 * These two blocks are splitted because this patch is a copy of what I'm about to ask for #485 (bugs.typo3.org)
		 * But to avoid that this XCLASS covers the process_datamap() aswell this hookObj initialization is inlined in this version
		 */
		if($hookObjectsArr === false) {
			$hookObjectsArr = array();
			if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'])) {
				foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'] as $classRef) {
					$hookObjectsArr[] = t3lib_div::getUserObj($classRef);
				}
			}
		}

		/**
		 * This part comes from #485 (bugs.typo3.org)
		 */
		$res = null;
		if (is_array($hookObjectsArr))	{
			foreach($hookObjectsArr as $hookObj)	{
				if (method_exists($hookObj, 'checkRecordUpdateAccess')) {
					$res = $hookObj->checkRecordUpdateAccess($table, $id, $data, $res, $this);
				}
			}
		}
		if($res === 1 || $res === 0) {
			return $res;
		} else {
			$res = 0;
		}

		if ($TCA[$table] && intval($id)>0)	{
			if (isset($this->recUpdateAccessCache[$table][$id]))	{	// If information is cached, return it
				return $this->recUpdateAccessCache[$table][$id];
				// Check if record exists and 1) if 'pages' the page may be edited, 2) if page-content the page allows for editing
			} elseif ($this->doesRecordExist($table,$id,'edit'))	{
				$res = 1;
			}
			$this->recUpdateAccessCache[$table][$id]=$res;	// Cache the result
		}

		return $res;
	}
}

?>