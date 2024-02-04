<?php

declare(strict_types=1);

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Loader\XliffFileLoader;

final class XliffTest extends TestCase
{
    private XliffFileLoader $loader;

    /**
     * @var string[]
     */
    private array $errors = [];

    protected function setUp(): void
    {
        $this->loader = new XliffFileLoader();
    }

    /**
     * @dataProvider provideXliffCases
     */
    public function testXliff(string $path): void
    {
        $this->validatePath($path);
        if (\count($this->errors) > 0) {
            self::fail(sprintf('Unable to parse xliff files: %s', implode(', ', $this->errors)));
        }
    }

    /**
     * @return string[][]
     */
    public static function provideXliffCases(): iterable
    {
        return [[__DIR__.'/../../src/Resources/translations']];
    }

    private function validateXliff(string $file): void
    {
        try {
            $this->loader->load($file, 'en');
            self::assertTrue(true, sprintf('Successful loading file: %s', $file));
        } catch (InvalidResourceException $e) {
            $this->errors[] = sprintf('%s => %s', $file, $e->getMessage());
        }
    }

    private function validatePath(string $path): void
    {
        $files = glob(sprintf('%s/*.xlf', $path));

        if (false === $files) {
            return;
        }

        foreach ($files as $file) {
            $this->validateXliff($file);
        }
    }
}
