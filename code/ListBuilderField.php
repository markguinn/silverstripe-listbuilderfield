<?php
/**
 * A 'list builder' field is two lists (jquery ui sortables), a master
 * list and a 'selected' list.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package listbuilderfield
 * @date 06.09.2013
 */
class ListBuilderField extends CheckboxSetField
{
	protected $sortField = 'Sort';
	protected $masterTitle = 'All Items';
	protected $selectedTitle = 'Selected';

	/**
	 * @param string $name
	 * @param string $title [optional]
	 * @param array $source [optional]
	 * @param string $sortField [optional] - default is 'Sort'
	 * @param string $value [optional]
	 * @param Form $form [optional]
	 */
	public function __construct($name, $title=null, $source=array(), $sortField='Sort', $value='', $form=null) {
		parent::__construct($name, $title, $source, $value, $form);
		$this->setSortField($sortField);
	}


	/**
	 * Load a value into this CheckboxSetField
	 */
	public function setValue($value, $obj = null) {
		// If we're not passed a value directly, we can look for it in a relation method on the object passed as a
		// second arg
		if(!$value && $obj && $obj instanceof DataObject && $obj->hasMethod($this->name)) {
			$funcName = $this->name;
			$join = $obj->$funcName();
			if ($join) {
				$join = $join->sort($this->getSortField());
				$value = $join->getIDList();
			} else {
				$value = array();
			}
		}

		parent::setValue($value, $obj);

		return $this;
	}

	/**
	 * @param array $properties
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript('listbuilderfield/javascript/ListBuilderField.js');
		Requirements::css('listbuilderfield/css/ListBuilderField.css');
		$source = $this->source;
		$values = $this->value;
		$items = array();

		// Get values from the join, if available
		if (is_object($this->form)) {
			$record = $this->form->getRecord();
			if(!$values && $record && $record->hasMethod($this->name)) {
				$funcName = $this->name;
				$join = $record->$funcName();
				if ($join) {
					$join = $join->sort($this->getSortField());
					foreach($join as $joinItem) {
						$values[] = $joinItem->ID;
					}
				}
			}
		}

		// Source is not an array
		if (!is_array($source) && !is_a($source, 'SQLMap')) {
			if (is_array($values)) {
				$items = $values;
			} else {
				// Source and values are DataObject sets.
				if($values && is_a($values, 'SS_List')) {
					foreach($values as $object) {
						if(is_a($object, 'DataObject')) {
							$items[] = $object->ID;
						}
					}
				} elseif($values && is_string($values)) {
					$items = explode(',', $values);
					$items = str_replace('{comma}', ',', $items);
				}
			}
		} else {
			// Sometimes we pass a singluar default value thats ! an array && !SS_List
			if($values instanceof SS_List || is_array($values)) {
				$items = $values;
			} else {
				$items = explode(',', $values);
				$items = str_replace('{comma}', ',', $items);
			}
		}

		if(is_array($source)) {
			unset($source['']);
		}

		$odd = 0;
		$options = array();
		$selected = array();
		if ($source == null) $source = array();

		if ($source) {
			foreach ($source as $value => $item) {
				if ($item instanceof DataObject) {
					$value = $item->ID;
					$title = $item->Title;
				} else {
					$title = $item;
				}

				$itemID = $this->ID() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $value);
				$odd = ($odd + 1) % 2;
				$extraClass = $odd ? 'odd' : 'even';
				$extraClass .= ' val' . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $value);

				if (!in_array($value, $items) && !in_array($value, $this->defaultItems)) {
					$options[] = new ArrayData(array(
						'ID' => $itemID,
						'Class' => $extraClass,
						'Name' => "{$this->name}[{$value}]",
						'Value' => $value,
						'Title' => $title,
						'isDisabled' => $this->disabled || in_array($value, $this->disabledItems)
					));
				}
			}

			foreach ($items as $k => $v) {
				$item = $source[$k];
				if ($item instanceof DataObject) {
					$value = $item->ID;
					$title = $item->Title;
				} else {
					$title = $item;
					$value = $k;
				}

				$selected[] = new ArrayData(array(
					'Value' => $value,
					'Title' => $title,
				));
			}
		}

		$properties = array_merge($properties, array(
			'Options'   => new ArrayList($options),
			'Selected'  => new ArrayList($selected),
		));

		return $this->customise($properties)->renderWith($this->getTemplates());
	}


	/**
	 * Save the current value of this CheckboxSetField into a DataObject.
	 * If the field it is saving to is a has_many or many_many relationship,
	 * it is saved by setByIDList(), otherwise it creates a comma separated
	 * list for a standard DB text/varchar field.
	 *
	 * @param DataObjectInterface $record The record to save into
	 */
	public function saveInto(DataObjectInterface $record) {
		$fieldname = $this->name;
		$relation = ($fieldname && $record && $record->hasMethod($fieldname)) ? $record->$fieldname() : null;
		if ($fieldname && $record && $relation && ($relation instanceof RelationList || $relation instanceof UnsavedRelationList)) {
			// save the correct values
			$idList = array();

			if ($this->value) {
				$idList = is_array($this->value) ? array_values($this->value) : explode(',', $this->value);
			}

			$relation->setByIDList($idList);

			// put them in the right order
			// This may be drastic overkill, this bit is pulled form
			// GridFieldOrderable. I suspect we could simplify here?
			$ids   = $idList;
			$items = $list  = $relation;
			$field = $this->getSortField();
//			$items = $list->byIDs($ids)->sort($field);
//
//			// Ensure that each provided ID corresponded to an actual object.
//			if(count($items) != count($ids)) {
//				$this->httpError(404);
//			}

			// Populate each object we are sorting with a sort value.
			$this->populateSortValues($items);

			// Generate the current sort values.
			$current = $items->map('ID', $field)->toArray();

			// Perform the actual re-ordering.
			$this->reorderItems($list, $current, $ids);

		} elseif ($fieldname && $record) {
			$record->$fieldname = $this->getStringValue();
		}
	}


	/**
	 * @return string
	 */
	public function getStringValue() {
		if ($this->value) {
			$this->value = str_replace(',', '{comma}', $this->value);
			return implode(',', (array) $this->value);
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 */
	public function getSortField() {
		return $this->sortField;
	}

	/**
	 * @param string $val
	 * @return $this
	 */
	public function setSortField($val) {
		$this->sortField = $val;
		return $this;
	}

	/**
	 * @param string $masterTitle
	 * @return $this
	 */
	public function setMasterTitle($masterTitle) {
		$this->masterTitle = $masterTitle;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMasterTitle() {
		return $this->masterTitle;
	}

	/**
	 * @param string $selectedTitle
	 * @return $this
	 */
	public function setSelectedTitle($selectedTitle) {
		$this->selectedTitle = $selectedTitle;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSelectedTitle() {
		return $this->selectedTitle;
	}

	/**
	 * @return string
	 */
	public function Type() {
		return 'optionset checkboxset listbuilder';
	}


	// The following methods are stolen/modified from ajshort's gridfield extensions //////////////////////////////


	/**
	 * Gets the table which contains the sort field.
	 *
	 * @param DataList $list
	 * @return string
	 * @throws Exception
	 */
	public function getSortTable(DataList $list) {
		$field = $this->getSortField();

		if($list instanceof ManyManyList) {
			$extra = $list->getExtraFields();
			$table = $list->getJoinTable();

			if(array_key_exists($field, $extra)) {
				return $table;
			}
		}

		$classes = ClassInfo::dataClassesFor($list->dataClass());

		foreach($classes as $class) {
			if(singleton($class)->hasOwnTableDatabaseField($field)) {
				return $class;
			}
		}

		throw new Exception("Couldn't find the sort field '$field'");
	}


	/**
	 * @param DataList $list
	 * @param array $values
	 * @param array $order
	 */
	protected function reorderItems(DataList $list, array $values, array $order) {
		// Get a list of sort values that can be used.
		$pool = array_values($values);
		sort($pool);

		// Loop through each item, and update the sort values which do not
		// match to order the objects.
		foreach(array_values($order) as $pos => $id) {
			if($values[$id] != $pool[$pos]) {
				DB::query(sprintf(
					'UPDATE "%s" SET "%s" = %d WHERE %s',
					$this->getSortTable($list),
					$this->getSortField(),
					$pool[$pos],
					$this->getSortTableClauseForIds($list, $id)
				));
			}
		}
	}

	/**
	 * @param DataList $list
	 */
	protected function populateSortValues(DataList $list) {
		$list   = clone $list;
		$field  = $this->getSortField();
		$table  = $this->getSortTable($list);
		$clause = sprintf('"%s"."%s" = 0', $table, $this->getSortField());

		foreach($list->where($clause)->column('ID') as $id) {
			$max = DB::query(sprintf('SELECT MAX("%s") + 1 FROM "%s"', $field, $table));
			$max = $max->value();

			DB::query(sprintf(
				'UPDATE "%s" SET "%s" = %d WHERE %s',
				$table,
				$field,
				$max,
				$this->getSortTableClauseForIds($list, $id)
			));
		}
	}

	/**
	 * @param DataList $list
	 * @param $ids
	 * @return string
	 */
	protected function getSortTableClauseForIds(DataList $list, $ids) {
		if(is_array($ids)) {
			$value = 'IN (' . implode(', ', array_map('intval', $ids)) . ')';
		} else {
			$value = '= ' . (int) $ids;
		}

		if($list instanceof ManyManyList) {
			$extra = $list->getExtraFields();
			$key   = $list->getLocalKey();

			if(array_key_exists($this->getSortField(), $extra)) {
				return sprintf('"%s" %s', $key, $value);
			}
		}

		return "\"ID\" $value";
	}

}