<?php

namespace TopConcepts\Klarna\Tests\Codeception;

use Codeception\Example;
use Exception;
use OxidEsales\Codeception\Step\Basket;
use TopConcepts\Klarna\Core\KlarnaConsts;


class NavigationFrontendKpCest
{

    /**
     * @group KP_frontend
     * @param AcceptanceTester $I
     * @throws Exception
     */
    public function testB2BOrder(AcceptanceTester $I)
    {
        $I->loadKlarnaAdminConfig('KP', 'B2BOTH');

        //Navigate untill step 3
        $this->navigateToPay($I, 'DE', true);
        $I->wait(6);
        $I->selectOption('form input[name=paymentid]', 'Pay Later');
        $I->click(".nextStep");
        $I->waitForElementVisible('//*[@id="klarna-pay-later-fullscreen"]');
        $I->switchToIFrame("klarna-pay-later-fullscreen");
        $I->wait(2);
        $I->click('//*[@id="organizationalData-dataCollection__entityType__container"]');
        $I->click('//*[@id="organizationalData-entityType__limited_company"]');
        $I->click('//*[@id="organizationalData-dataCollection__organizationNumber"]');
        $this->fillFieldSpecial('//*[@id="organizationalData-dataCollection__organizationNumber"]', "HRB12345", $I);
        $this->fillFieldSpecial('//*[@id="organizationalData-dataCollection__vatId"]', "DE999999999", $I);
        $I->click('//*[@id="organizationalData-dataCollection__submit"]');
        $I->switchToIFrame();
        $I->wait(2);
        $I->click(".nextStep");
        $I->waitForPageLoad();
        $I->wait(2);
        $I->seeInCurrentUrl('thankyou');
        $I->assertKlarnaData();
    }

    /**
     * @group KP_frontend
     * @throws Exception
     */
    public function testKpPayNowDebitOrder(AcceptanceTester $I)
    {
        $I->loadKlarnaAdminConfig('KP');

        //Navigate untill step 3
        $this->navigateToPay($I);
        $I->wait(6);
        $I->selectOption('form input[name=paymentid]', 'Pay Now');
        $I->switchToIFrame("klarna-pay-now-main");
        $I->click('//*[@id="installments-direct_debit|-1"]');
        $I->wait(2);
        $I->switchToIFrame();
        $I->click(".nextStep");
        $I->wait(2);
        $I->switchToIFrame('klarna-pay-now-fullscreen');
        $this->fillFieldSpecial('//*[@id="purchase-approval-date-of-birth"]',$I->getKlarnaDataByName('sKlarnaBDate'), $I);
        $this->fillFieldSpecial('//*[@id="purchase-approval-phone-number"]',$I->getKlarnaDataByName('sKlarnaPhoneNumber'), $I);

        $I->click('//*[@id="purchase-approval-continue"]');
        $I->wait(2);

        try {
            //Check if IBAN needs to be filled
            $I->seeElement('//*[@id="iban"]');
            $I->waitForElementClickable('//*[@id="iban"]');
            $this->fillFieldSpecial('//*[@id="iban"]', $I->getKlarnaDataByName('sKlarnaPayNowIbanDE'), $I);
            $I->click('//*[@id="mandate-signup-sepa-iban__footer-button-wrapper"]');
            $I->waitForElementClickable('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
            $I->click('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
        } catch (Exception $e) {
            $I->waitForElementClickable('//*[@id="direct-debit-mandate-review__bottom"]');
            $I->click('//*[@id="direct-debit-mandate-review__bottom"]/span/button');
            $I->waitForElementClickable('//*[@id="direct-debit-confirmation__bottom"]');
            $I->click('//*[@id="direct-debit-confirmation__bottom"]/span/button');
        }

        $I->switchToIFrame();
        $I->wait(2);
        $I->click(".nextStep");
        $I->waitForPageLoad();
        $I->wait(2);
        $I->seeInCurrentUrl('thankyou');
        $I->assertKlarnaData();
    }

    /**
     * @group KP_frontend
     * @param AcceptanceTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider klarnaKPMethodsProvider
     */
    public function testFrontendKpOrder(AcceptanceTester $I, Example $data)
    {
        $I->clearShopCache();
        $I->loadKlarnaAdminConfig('KP');

        if(empty($data['country'])) {
            $data['country'] = null;
        }

        //Navigate untill step 3
        $this->navigateToPay($I, $data['country']);

        //step 3
        $I->wait(6);
        $I->click("//input[@value='".$data['radio']."']");

        $I->click(".nextStep");
        $I->wait(2);
        if($data['country'] != 'GB') {
            $I->switchToIFrame($data['iframe']);

            if($data['country'] == 'FI' || $data['country'] == 'DK' || $data['country'] == 'NO' || $data['country'] == 'SE'){

                $phone = $I->getKlarnaDataByName('sKlarnaPhoneNumber');
                switch ($data['country'])
                {
                    case "FI":
                        $number = "311280-999J";
                        break;
                    case "DK":
                        $number = "171035-4509";
                        $phone = "41468007";
                        break;
                    case "NO":
                        $number = "01018043587";
                        $phone = "48404583";
                        break;
                    case "SE":
                        $number = "880330-7019";
                        break;
                    default:
                        $number = "";
                }

                $this->fillFieldSpecial('//*[@id="purchase-approval-national-identification-number"]',$number, $I);
                $this->fillFieldSpecial('//*[@id="purchase-approval-phone-number"]',$phone, $I);
            } else {
                $this->fillFieldSpecial('//*[@id="purchase-approval-date-of-birth"]',$I->getKlarnaDataByName('sKlarnaBDate'), $I);
                $this->fillFieldSpecial('//*[@id="purchase-approval-phone-number"]',$I->getKlarnaDataByName('sKlarnaPhoneNumber'), $I);
            }
            $I->click('//*[@id="purchase-approval-continue"]');
        }

        if($data['iframe'] == 'klarna-pay-now-fullscreen') {
            try {
                //Check if IBAN needs to be filled
                $I->seeElement('//*[@id="iban"]');
                $I->waitForElementClickable('//*[@id="iban"]');
                $this->fillFieldSpecial('//*[@id="iban"]', $I->getKlarnaDataByName('sKlarnaPayNowIbanDE'), $I);
                $I->click('//*[@id="mandate-signup-sepa-iban__footer-button-wrapper"]');
                $I->waitForElementClickable('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
                $I->click('//*[@id="mandate-signup-sepa-details-confirmation__footer-button-wrapper"]');
            } catch (Exception $e) {
                $I->waitForElementClickable('//*[@id="direct-debit-mandate-review__bottom"]');
                $I->click('//*[@id="direct-debit-mandate-review__bottom"]');
                $I->waitForElementClickable('//*[@id="direct-debit-confirmation__bottom"]');
                $I->click('//*[@id="direct-debit-confirmation__bottom"]');
            }
        }

        $I->switchToIFrame();
        $I->waitForElementVisible(".nextStep");
        $I->wait(3);
        $I->click(".nextStep");
        $I->waitForPageLoad();
        $I->wait(2);
        $I->seeInCurrentUrl('thankyou');
    }

    /**
     * @return array
     */
    protected function klarnaKPMethodsProvider()
    {

        return [
            ['title' => 'Pay Later', 'radio' => 'klarna_pay_later', 'iframe' => 'klarna-pay-later-fullscreen', 'country' => null],
            ['title' => 'Pay Later','radio' => 'klarna_pay_later', 'iframe' => 'klarna-pay-later-fullscreen','country' => 'AT'],
            ['title' => 'Pay Later','radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'DK'],
            ['title' => 'Pay Later','radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'FI'],
            ['title' => 'Pay Later','radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'NL'],
            ['title' => 'Pay Later','radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'NO'],
            ['title' => 'Pay Later','radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'SE'],
            ['title' => 'Pay Later','radio' => 'klarna_pay_later','iframe' => 'klarna-pay-later-fullscreen','country' => 'GB'],
            ['title' => 'Slice It','radio' => 'klarna_slice_it','iframe' => 'klarna-pay-over-time-fullscreen','country' => null],
            ['title' => 'Pay Now','radio' => 'klarna_pay_now','iframe' => 'klarna-pay-now-fullscreen','country' => null],
        ];
    }

    /**
     * @param null $country
     * @param bool $isB2B
     * @param AcceptanceTester $I
     * @throws Exception
     */
    protected function navigateToPay(AcceptanceTester $I, $country = null, $isB2B = false)
    {
        $userLogin = "user";
        $basket = new Basket($I);
        $homePage = $I->openShop();
        $basket->addProductToBasket('05848170643ab0deb9914566391c0c63', 1);
        $homePage->openMiniBasket()->openCheckout();
        $I->waitForElementClickable(".nextStep");
        if ($country) {
            $I->switchCurrency(KlarnaConsts::getCountry2CurrencyArray()[$country]);
           $userLogin = "user_".strtolower($country);

           if($isB2B){
               $userLogin .= "_b2b";
           }
        }

        $homePage->loginUser($userLogin."@oxid-esales.com", "12345");

        //step 2
        $I->canSee('Billing address');
        $I->click(".nextStep");
    }

    /**
     * Fill the field character by character
     * @param string $input
     * @param string $msg
     * @param AcceptanceTester $I
     */
    protected function fillFieldSpecial(string $input, string $msg, AcceptanceTester $I)
    {
        foreach (str_split($msg) as $key) {
            $I->pressKey($input, $key);
        }
    }
}
