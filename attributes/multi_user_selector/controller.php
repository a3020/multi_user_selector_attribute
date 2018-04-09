<?php

namespace Concrete\Package\MultiUserSelectorAttribute\Attribute\MultiUserSelector;

use Concrete\Core\Attribute\Controller as CoreController;
use Concrete\Core\Support\Facade\UserInfo;
use Database;
use Core;

class controller extends CoreController
{
	public function getRawValue()
	{
        if (!$this->attributeValue) {
            return null;
        }

        if (!$this->attributeValue->getGenericValue()) {
            return null;
        }

        $db = Database::connection();
        $value = $db->fetchColumn("SELECT value FROM atMultiUserSelector WHERE avID = ?", [
            $this->attributeValue->getAttributeValueID(),
        ]);

        return trim($value);
	}

    public function getDisplayValue()
    {
        $userNames = array_map(function($user) {
            /** @var UserInfo $user */
            return $user->getUserName();
        }, $this->getValue());

        return implode(', ', $userNames);
    }

    /**
     * @return array
     */
    public function getValue()
	{
		$value = $this->getRawValue();
		$users = [];

		$ids = explode(',', $value);

		foreach ($ids as $uID) {
			$ui = UserInfo::getByID($uID);
			if ($ui) {
				$users[] = $ui;
			}
		}

		return $users;
	}

    public function getSearchIndexValue()
    {
        if (!$this->attributeValue) {
            return null;
        }

        $value = (string) $this->attributeValue;

        $str = "\n";
        if (strlen($value)) {
            $list = explode(',', $value);
            foreach ($list as $l) {
                $l = (is_object($l) && method_exists($l, '__toString')) ? $l->__toString() : $l;
                $str .= $l . "\n";
            }
        }

        // remove line break for empty list
        if ($str === "\n") {
            return '';
        }

        return $str;
    }

    public function search()
    {
        $this->form();
    }

    public function searchForm($list)
    {
        $value = $this->request($this->field('value'));
        $values = explode(',', $value);
        $db = Database::get();
        $tbl = $this->attributeKey->getIndexedSearchTable();

        $i = 0;
        $multiString = '';
        foreach ($values as $val) {
            $val = $db->quote('%||' . $val . '||%');
            $multiString .= 'REPLACE(ak_' . $this->attributeKey->getAttributeKeyHandle() . ', "\n", "||") like ' . $val . ' ';
            if (($i + 1) < count($values)) {
                $multiString .= 'OR ';
            }
            ++$i;
        }
        $list->filter(false, '(' . $multiString . ')');

        return $list;
    }

    public function form()
    {
        $this->load();
        $values = [];

        if (is_object($this->attributeValue)) {
            $values = $this->getAttributeValue()->getValue();
        }

        echo $this->selectMultipleUsers($this->field('value'), $values) .
            '<script>$(function() { $.fn.dialog.activateDialogContents($(".form-group")); }); </script>';
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
        if (!$this->attributeValue) {
            return;
        }

        $db = Database::connection();

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        if (!$value) {
            $value = '';
        }

        $db->Replace('atMultiUserSelector', [
            'avID' => $this->attributeValue->getAttributeValueID(),
            'value' => $value,
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
		foreach ($this->attributeKey->getAttributeValueIDList() as $id) {
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
        if (!$this->getAttributeValueObject()) {
            return null;
        }

        $db = Database::connection();
		$db->query("DELETE FROM atMultiUserSelector where avID = ?", [
			$this->getAttributeValueID()
		]);
	}

    public function selectMultipleUsers($fieldName, $users = [])
    {
        $id = uniqid();

        $html = '';
        $html .= '<table id="ccmUserSelect' . $id . '" class="table table-condensed" cellspacing="0" cellpadding="0" border="0">';
        $html .= '<tr>';
        $html .= '<th>' . t('Username') . '</th>';
        $html .= '<th>' . t('Email Address') . '</th>';
        $html .= '<th style="width: 1px"><a class="icon-link ccm-user-select-item dialog-launch" dialog-append-buttons="true" dialog-width="90%" dialog-height="70%" dialog-modal="false" dialog-title="' . t('Choose User') . '" href="' . \URL::to('/ccm/system/dialogs/user/search') . '"><i class="fa fa-plus-circle" /></a></th>';
        $html .= '</tr><tbody id="ccmUserSelect' . $id . '_body" >';
        foreach ($users as $ui) {
            $html .= '<tr id="ccmUserSelect' . $id . '_' . $ui->getUserID() . '" class="ccm-list-record">';
            $html .= '<td><input type="hidden" name="' . $fieldName . '[]" value="' . $ui->getUserID() . '" />' . $ui->getUserName() . '</td>';
            $html .= '<td>' . $ui->getUserEmail() . '</td>';
            $html .= '<td><a href="javascript:void(0)" class="ccm-user-list-clear icon-link"><i class="fa fa-minus-circle ccm-user-list-clear-button"></i></a>';
            $html .= '</tr>';
        }
        if (count($users) == 0) {
            $html .= '<tr class="ccm-user-selected-item-none"><td colspan="3">' . t('No users selected.') . '</td></tr>';
        }
        $html .= '</tbody></table><script type="text/javascript">
		$(function() {
			$("#ccmUserSelect' . $id . ' .ccm-user-select-item").dialog();
			$("a.ccm-user-list-clear").click(function() {
				$(this).parents(\'tr\').remove();
			});

			$("#ccmUserSelect' . $id . ' .ccm-user-select-item").on(\'click\', function() {
				ConcreteEvent.subscribe(\'UserSearchDialogSelectUser\', function(e, data) {
					var uID = data.uID, uName = data.uName, uEmail = data.uEmail;
					e.stopPropagation();
					$("tr.ccm-user-selected-item-none").hide();
					if ($("#ccmUserSelect' . $id . '_" + uID).length < 1) {
						var html = "";
						html += "<tr id=\"ccmUserSelect' . $id . '_" + uID + "\" class=\"ccm-list-record\"><td><input type=\"hidden\" name=\"' . $fieldName . '[]\" value=\"" + uID + "\" />" + uName + "</td>";
						html += "<td>" + uEmail + "</td>";
						html += "<td><a href=\"javascript:void(0)\" class=\"ccm-user-list-clear icon-link\"><i class=\"fa fa-minus-circle ccm-user-list-clear-button\" /></a>";
						html += "</tr>";
						$("#ccmUserSelect' . $id . '_body").append(html);
					}
					$("a.ccm-user-list-clear").click(function() {
						$(this).parents(\'tr\').remove();
					});
				});
				ConcreteEvent.subscribe(\'UserSearchDialogAfterSelectUser\', function(e) {
					jQuery.fn.dialog.closeTop();
				});
			});
		});

		</script>';

        return $html;
    }
}
