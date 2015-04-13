<?php

namespace Inviqa;

use Composer\Script\PackageEvent;
use Composer\DependencyResolver\SolverProblemsException;
use Composer\DependencyResolver\Problem;
use Composer\DependencyResolver\Pool;
use Composer\Package\Package;
use Composer\Package\Version\VersionParser;

/**
 * Ensures that a Magento module has the expected Magento version installed.
 */
class MagentoComposer
{
    const PACKAGE_TYPE_MAGENTO = 'magento-module';

    const MESSAGE_WRONG_MAGENTO_ROOT = 'Mage class not found in path %s. Please specify the correct "magento-root-dir" value in extra section!';
    const MESSAGE_EMPTY_MAGENTO_ROOT = 'The key "magento-root-dir" is missing from extra configuration section.';
    const MESSAGE_EMPTY_EXPECTED_VERSION = 'The key "magento-version" is missing from extra configuration section.';
    const MESSAGE_VERSON_MISMATCH = "Magento %s could not be found! Current Magento version is %s.";

    public static function checkVersion(PackageEvent $event)
    {
        $self = new self;
        $rootExtra = $event->getComposer()->getPackage()->getExtra();
        $packageExtra = $event->getOperation()->getPackage()->getExtra();

        if (!$self->isMagentoRequired($event->getOperation()->getPackage())) {
            return;
        }

        $expectedVersionConstraint = $self->getExpectedMagentoVersion($packageExtra);
        $currentVersionConstraint = $self->getCurrentMagentoVersion($rootExtra);

        if (!$currentVersionConstraint->matches($expectedVersionConstraint)) {
            throw $self->createException(
                sprintf(
                    self::MESSAGE_VERSON_MISMATCH,
                    $expectedVersionConstraint->getPrettyString(),
                    $currentVersionConstraint->getPrettyString()
                )
            );
        }
    }

    private function isMagentoRequired(Package $package)
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

    private function getExpectedMagentoVersion(array $extra)
    {
        if (empty($extra['magento-version'])) {
            throw $this->createException(self::MESSAGE_EMPTY_EXPECTED_VERSION);
        }

        $versionParser = new VersionParser();
        return $versionParser->parseConstraints($extra['magento-version']);
    }

    private function getCurrentMagentoVersion(array $extra)
    {
        if (empty($extra['magento-root-dir'])) {
            throw $this->createException(self::MESSAGE_EMPTY_MAGENTO_ROOT);
        }

        $mageFile = $extra['magento-root-dir'] . '/app/Mage.php';
        @include_once $mageFile;
        if (!class_exists('\Mage', false)) {
            throw $this->createException(sprintf(self::MESSAGE_WRONG_MAGENTO_ROOT, $mageFile));
        }

        $versionParser = new VersionParser();
        return $versionParser->parseConstraints(\Mage::getVersion());
    }
}
