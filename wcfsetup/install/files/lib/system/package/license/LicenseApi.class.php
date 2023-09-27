<?php

namespace wcf\system\package\license;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;
use GuzzleHttp\Psr7\Request;
use wcf\data\package\update\server\PackageUpdateServer;
use wcf\system\io\HttpFactory;
use wcf\system\package\license\exception\MissingCredentials;
use wcf\system\package\license\exception\ParsingFailed;

/**
 * Provides access to the license data.
 *
 * @author Alexander Ebert
 * @copyright 2001-2023 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.0
 */
final class LicenseApi
{
    private readonly LicenseData $data;

    private const LICENSE_FILE = \WCF_DIR . 'license.php';

    private function __construct(LicenseData $data)
    {
        $this->data = $data;
    }

    public function getData(): LicenseData
    {
        return $this->data;
    }

    public function updateLicenseFile(): void
    {
        @\file_put_contents(
            self::LICENSE_FILE,
            \sprintf(
                <<<'EOT'
                <?php
                /* GENERATED AT %s -- DO NOT EDIT */
                return unserialize(%s);
                EOT,
                \gmdate('r', \TIME_NOW),
                \var_export(\serialize($this->data), true),
            )
        );
    }

    private static function parseLicenseData(string $json): LicenseData
    {
        try {
            return (new MapperBuilder())
                ->allowSuperfluousKeys()
                ->mapper()
                ->map(
                   LicenseData::class,
                    Source::json($json)
                );
        } catch (MappingError $e) {
            throw new ParsingFailed($e);
        }
    }

    public static function fetchFromRemote(array $authData = []): LicenseApi
    {
        if ($authData === []) {
            if (!self::hasLicenseCredentials()) {
                throw new MissingCredentials();
            }

            $authData = PackageUpdateServer::getWoltLabUpdateServer()->getAuthData();
        }

        $request = new Request(
            'POST',
            'https://api.woltlab.com/2.1/customer/license/list.json',
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ],
            \http_build_query([
                'licenseNo' => $authData['username'],
                'serialNo' => $authData['password'],
                'instanceId' => \hash_hmac('sha256', 'api.woltlab.com', \WCF_UUID),
            ], '', '&', \PHP_QUERY_RFC1738)
        );

        $response = HttpFactory::makeClientWithTimeout(5)->send($request);

        return new LicenseApi(self::parseLicenseData($response->getBody()));
    }

    public static function readFromFile(): ?LicenseApi
    {
        if (!\is_readable(self::LICENSE_FILE)) {
            return null;
        }

        return new LicenseApi(require(self::LICENSE_FILE));
    }

    public static function removeLicenseFile(): void
    {
        if (!\file_exists(self::LICENSE_FILE)) {
            return;
        }

        \unlink(self::LICENSE_FILE);
    }

    public static function hasLicenseCredentials(): bool
    {
        $authData = PackageUpdateServer::getWoltLabUpdateServer()->getAuthData();
        if (empty($authData['username']) || empty($authData['password'])) {
            return false;
        }

        return true;
    }
}
