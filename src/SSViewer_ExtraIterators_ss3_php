<?php

/**
 * Provides some extra iterator properties to SSviewer when looping in templates
 */

class SSViewer_ExtraIterators implements TemplateIteratorProvider {

	protected $iteratorPos;  // 0 based
	protected $iteratorTotalItems;

	public static function get_template_iterator_variables() {
		return array(
			'GroupSize',
			'PosInGroup',
			'FirstOfGroup',
			'LastOfGroup',
			'FirstLastOfGroup',
			'GroupOfGroups',
		);
	}

	/**
	 * Set the current iterator properties - where we are on the iterator.
	 *
	 * @param int $pos position in iterator
	 * @param int $totalItems total number of items
	 */
	public function iteratorProperties($pos, $totalItems) {
		$this->iteratorPos        = $pos;
		$this->iteratorTotalItems = $totalItems;
	}

	/**
	 * Group methods
	 *
	 * @return int
	 */
	public function GroupSize($divideInGroups = false) {
		if(!$divideInGroups) return $this->iteratorTotalItems;
		return ceil($this->iteratorTotalItems/$divideInGroups);
	}
	
	public function PosInGroup($divideInGroups = false, $startIndex = 1) {
		return $this->iteratorPos % $this->GroupSize($divideInGroups) + $startIndex;
	}
	
	public function FirstOfGroup($divideInGroups = false) {
		return $this->PosInGroup($divideInGroups) == 1;
	}
	
	public function LastOfGroup($divideInGroups) {
		return $this->PosInGroup($divideInGroups) == $this->GroupSize($divideInGroups)
				|| $this->iteratorPos == $this->iteratorTotalItems -1;
	}

	public function FirstLastOfGroup($divideInGroups) {
		if($this->FirstOfGroup($divideInGroups) && $this->LastOfGroup($divideInGroups)) {
			return 'first-of-group last-of-group';
		}
		if($this->FirstOfGroup($divideInGroups)) return 'first-of-group';
		if($this->LastOfGroup($divideInGroups))  return 'last-of-group';
	}
	
	public function GroupOfGroups($divideInGroups = false) {
		$groupsize = $this->GroupSize($divideInGroups);
		return ceil( ($this->iteratorPos + 1) / $groupsize ); //pos = 0 based
	}

}