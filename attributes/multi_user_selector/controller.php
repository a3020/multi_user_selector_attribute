<?php
namespace Concrete\Package\MultiUserSelectorAttribute\Attribute\MultiUserSelector;

use Concrete\Core\Attribute\Controller as CoreController;
use Core;
use Database;

class Controller extends CoreController
{
	public function getRawValue()
	{
		$db = Database::connection();
		$value = $db->fetchColumn("SELECT value FROM atMultiUserSelector WHERE avID = ?", [
			$this->getAttributeValueID()
		]);

		return trim($value);
	}

	public function getValue() : array
	{
		$value = $this->getRawValue() ?? "";
		$users = [];

		$ids = explode(',', $value);

		foreach ($ids as $uID) {
			$ui = Core::make(\Concrete\Core\User\UserInfoFactory::class)->getByID($uID);
			if ($ui) {
				$users[] = $ui;
			}
		}

		return $users;
	}

	public function form()
	{
		$this->load();
		$values = [];

		if (is_object($this->attributeValue)) {
			$values = $this->getAttributeValue()->getValue();
		}

		$us = Core::make('helper/form/user_selector');

		// Hack to get the user search dialog working in composer
		echo $us->selectMultipleUsers($this->field('value'), $values) .
			'<script>$.fn.dialog.activateDialogContents($(".form-group"));</script>';
	}

	protected function load()
	{
		$ak = $this->getAttributeKey();
		if (!is_object($ak)) {
			return false;
		}
	}

	public function saveValue($value)
	{
		$db = Database::connection();

		if (is_array($value)) {
			$value = implode(',', $value);
		}

		if (!$value) {
			$value = '';
		}

		$db->Replace('atMultiUserSelector', [
			'avID' => $this->getAttributeValueID(),
			'value' => $value
		], 'avID', true);
	}

	public function saveKey($data)
	{
		$db = Database::connection();
		$ak = $this->getAttributeKey();
	}

	public function deleteKey()
	{
		$db = Database::connection();
		$arr = $this->attributeKey->getAttributeValueIDList();
		foreach ($arr as $id) {
			$db->query("DELETE FROM atMultiUserSelector WHERE avID = ?", [
				$id
			]);
		}
	}

	public function saveForm($data)
	{
		$this->saveValue($data['value']);
	}

	public function deleteValue()
	{
		$db = Database::connection();
		$db->query("DELETE FROM atMultiUserSelector where avID = ?", [
			$this->getAttributeValueID()
		]);
	}
}
