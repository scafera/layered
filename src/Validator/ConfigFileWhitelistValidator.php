<?php

declare(strict_types=1);

namespace Scafera\Layered\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\ToleratedFiles;

final class ConfigFileWhitelistValidator implements ValidatorInterface
{
    private const ALLOWED = ['config.yaml', 'config.local.yaml'];

    public function getId(): string
    {
        return 'layered.config-file-whitelist';
    }

    public function getName(): string
    {
        return 'Config directory whitelist';
    }

    public function validate(string $projectDir): array
    {
        $configDir = $projectDir . '/config';
        if (!is_dir($configDir)) {
            return [];
        }

        $violations = [];
        $iterator = new \FilesystemIterator($configDir, \FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $entry) {
            $name = $entry->getFilename();

            if ($entry->isDir()) {
                $violations[] = 'config/' . $name . '/ is not allowed — only config.yaml and config.local.yaml are permitted in config/';
                continue;
            }

            if (in_array($name, ToleratedFiles::names(), true)) {
                continue;
            }

            if (!in_array($name, self::ALLOWED, true)) {
                $violations[] = 'config/' . $name . ' is not allowed — only config.yaml and config.local.yaml are permitted in config/';
            }
        }

        return $violations;
    }
}
