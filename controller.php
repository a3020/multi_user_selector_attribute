<?php

namespace Concrete\Package\MultiUserSelectorAttribute;

use Concrete\Core\Attribute\Key\Category as AttributeKeyCategory;
use Concrete\Core\Attribute\Type as AttributeType;
use Concrete\Core\Package\Package;

class controller extends Package
{
    protected $pkgHandle = 'multi_user_selector_attribute';
    protected $appVersionRequired = '8.0';
    protected $pkgVersion = '0.9.1';

    public function getPackageName()
    {
        return t('Multi User Selector Attribute');
    }

    public function getPackageDescription()
    {
        return t('Link one or more users to an entity');
    }

    public function install()
    {
        $pkg = parent::install();

        $at = AttributeType::add('multi_user_selector', t('Multi User Selector'), $pkg);

        $col = AttributeKeyCategory::getByHandle('collection');
        $col->associateAttributeKeyType($at);

        $col = AttributeKeyCategory::getByHandle('file');
        $col->associateAttributeKeyType($at);
    }
}
