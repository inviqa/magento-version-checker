<?php

namespace Inviqa;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\DependencyResolver\SolverProblemsException;
use Composer\DependencyResolver\Pool;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;

/**
 * Ensures that a Magento module has the expected Magento version installed.
 */
class MagentoComposer
{
    const PACKAGE_TYPE_MAGENTO = 'magento-module';

    const CONFIG_ROOT_STRICT = 'strict-check';

    // @codingStandardsIgnoreStart
    const MESSAGE_WRONG_MAGENTO_ROOT = 'Mage class not found in path %s. Please specify the correct "magento-root-dir" value in extra section!';
    const MESSAGE_EMPTY_MAGENTO_ROOT = 'The key "magento-root-dir" is missing from extra configuration section.';
    const MESSAGE_EMPTY_EXPECTED_VERSION = "The %s module doesn't support Magento %s edition or the '%s' is missing from the module's configuration.";
    const MESSAGE_VERSION_MISMATCH = "Magento %s could not be found! Current Magento version is %s.";
    // @codingStandardsIgnoreEnd

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var PackageEvent
     */
    private $event;

    public function __construct(Composer $composer, IOInterface $io, PackageEvent $event)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->event = $event;
    }

    /**
     * @return PackageInterface
     */
    private function getModuleToBeInstalled()
    {
        return $this->event->getOperation()->getPackage();
    }

    /**
     * @return PackageInterface
     */
    private function getRootPackage()
    {
        return $this->event->getComposer()->getPackage();
    }

    /**
     * @return bool
     */
    private function isStrictCheck()
    {
        $rootExtra = $this->getRootPackage()->getExtra();

        // disable strict check by default
        if (empty($rootExtra[self::CONFIG_ROOT_STRICT])) {
            return false;
        }
        return (bool) $rootExtra[self::CONFIG_ROOT_STRICT];
    }

    public function checkVersion()
    {
        $rootExtra = $this->getRootPackage()->getExtra();
        $packageExtra = $this->getModuleToBeInstalled()->getExtra();

        if (!$this->isMagentoRequired($this->getModuleToBeInstalled())) {
            return;
        }

        $this->loadMage($rootExtra);

        $expectedVersionConstraint = $this->getExpectedMagentoVersion($packageExtra);

        // some error happened
        if ($expectedVersionConstraint === false) {
            return;
        }

        $currentVersionConstraint = $this->getCurrentMagentoVersion($rootExtra);

        if (!$currentVersionConstraint->matches($expectedVersionConstraint)) {
            throw $this->createException(
                sprintf(
                    self::MESSAGE_VERSION_MISMATCH,
                    $expectedVersionConstraint->getPrettyString(),
                    $currentVersionConstraint->getPrettyString()
                )
            );
        }
    }

    private function isMagentoRequired(PackageInterface $package)
    {
        return $package->getType() == self::PACKAGE_TYPE_MAGENTO;
    }

    private function createException($message)
    {
        $problem = new ConfigurationProblem(new Pool, $message);
        $problems = [
            $problem
        ];
        return new SolverProblemsException($problems, []);
    }

    private function loadMage(array $extra)
    {
        if (empty($extra['magento-root-dir'])) {
            throw $this->createException(self::MESSAGE_EMPTY_MAGENTO_ROOT);
        }

        $mageFile = $extra['magento-root-dir'] . '/app/Mage.php';
        @include_once $mageFile;
        if (!class_exists('\Mage', false)) {
            throw $this->createException(sprintf(self::MESSAGE_WRONG_MAGENTO_ROOT, $mageFile));
        }
    }

    private function getExpectedMagentoVersion(array $extra)
    {
        $edition = \Mage::getEdition();
        $versionConfigKey = $this->getVersionConfigKey($edition);

        if (empty($versionConfigKey) || empty($extra[$versionConfigKey])) {
            $moduleName = $this->getModuleToBeInstalled()->getName();
            $errMsg = sprintf(self::MESSAGE_EMPTY_EXPECTED_VERSION, $moduleName, $edition, $versionConfigKey);
            if ($this->isStrictCheck()) {
                throw $this->createException($errMsg);
            } else {
                $this->io->writeError("<warning>$errMsg</warning>");
                return false;
            }
        }

        $versionParser = new VersionParser();
        return $versionParser->parseConstraints($extra[$versionConfigKey]);
    }

    private function getVersionConfigKey($edition)
    {
        switch ($edition) {
            case \Mage::EDITION_COMMUNITY:
                $versionConfigKey = 'magento-version-ce';
                break;

            case \Mage::EDITION_ENTERPRISE:
                $versionConfigKey = 'magento-version-ee';
                break;

            default:
                $versionConfigKey = '';
                break;
        }

        return $versionConfigKey;
    }

    private function getCurrentMagentoVersion()
    {
        $versionParser = new VersionParser();
        return $versionParser->parseConstraints(\Mage::getVersion());
    }
}
