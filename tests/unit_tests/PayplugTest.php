<?php
namespace Payplug;

/**
 * @group unit
 * @group ci
 * @group recommended
 */
class PayplugTest extends \PHPUnit_Framework_TestCase
{
    public function testCanInitializeConfiguration()
    {
        Payplug::setSecretKey('cba');

        $configuration = Payplug::getDefaultConfiguration();

        $this->assertEquals('cba', $configuration->getToken());
    }

    public function testCannotInitializeConfigurationWhenLiveTokenIsNotAString()
    {
        $this->setExpectedException('\PayPlug\Exception\ConfigurationException');

        Payplug::setSecretKey(true);
    }

    public function testCannotInitializeConfigurationWhenTestTokenIsArray()
    {
        $this->setExpectedException('\PayPlug\Exception\ConfigurationException');

        Payplug::setSecretKey(array(
                'LIVE_TOKEN'        => 'cba'
            )
        );
    }

    public function testCanGetAToken()
    {
        $configuration = Payplug::setSecretKey('cba');
        $this->assertEquals('cba', $configuration->getToken());
    }

    /**
     * @runInSeparateProcess so that static default configuration is cleared before the test
     */
    public function testThrowsExceptionWhenDefaultConfigurationIsNotSet()
    {
        $this->setExpectedException('\Payplug\Exception\ConfigurationNotSetException');
        Payplug::getDefaultConfiguration();
    }

    public function testCanSetDefaultConfiguration()
    {
        $configuration = Payplug::setSecretKey('abc');
        Payplug::setDefaultConfiguration($configuration);
        $this->assertEquals($configuration, Payplug::getDefaultConfiguration());
    }
}