<?php

namespace im\cms\tests\codeception\backend\acceptance;

use im\cms\tests\codeception\backend\AcceptanceTester;
use Yii;

class AcceptanceCest
{
    protected function loginAsAdmin(AcceptanceTester $I)
    {
        $this->login($I, 'admin', 'adminpass');
    }

    protected function loginAsUser(AcceptanceTester $I)
    {
        $this->login($I, 'user', 'userpass');
    }

    protected function login(AcceptanceTester $I, $name = 'admin', $password = 'adminpass')
    {
        $loginPage = LoginPage::openBy($I);
        $I->seeInTitle('Login');
        $loginPage->login($name, $password);
        if (method_exists($I, 'wait')) {
            $I->wait(2); // only for selenium
        }
        $I->see('Profile', '.nav');
    }

    protected function logout(AcceptanceTester $I)
    {
        if ($I->canSee('Profile', '.nav')) {
            $I->click('Profile', '.nav');
        }
        $I->see('Logout', '.nav');
        $I->click('Logout');
        if (method_exists($I, 'wait')) {
            $I->wait(2); // only for selenium
        }
        $I->see('Login', '.nav');
    }
}