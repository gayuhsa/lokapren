<?php

declare(strict_types=1);

namespace Tests\unit;

use App\Services\Rupiah;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RupiahTest extends CIUnitTestCase
{
    public function testFormatsUsingIndonesianSeparators(): void
    {
        $this->assertSame('Rp15.000', Rupiah::format(15000));
        $this->assertSame('Rp125.000', Rupiah::format(125000));
        $this->assertSame('Rp1.250.000', Rupiah::format(1250000));
        $this->assertSame('Rp0', Rupiah::format(0));
    }

    public function testNumberOmitsTheCurrencyPrefix(): void
    {
        $this->assertSame('125.000', Rupiah::number(125000));
    }

    public function testAcceptsNumericStringsFromTheDatabase(): void
    {
        // PostgreSQL BIGINT columns can come back as strings through the driver.
        $this->assertSame('Rp15.000', Rupiah::format('15000'));
    }

    /**
     * @dataProvider parseProvider
     */
    public function testParsesIndonesianInput(string $input, ?int $expected): void
    {
        $this->assertSame($expected, Rupiah::parse($input));
    }

    /**
     * @return iterable<string, array{0: string, 1: int|null}>
     */
    public static function parseProvider(): iterable
    {
        yield 'plain digits'      => ['15000', 15000];
        yield 'dot separated'     => ['15.000', 15000];
        yield 'with prefix'       => ['Rp15.000', 15000];
        yield 'lowercase prefix'  => ['rp15.000', 15000];
        yield 'multiple dots'     => ['1.250.000', 1250000];
        yield 'whitespace'        => [' 15.000 ', 15000];
        yield 'empty'             => ['', null];
        yield 'no digits'         => ['abc', null];
        yield 'zero'              => ['0', null];
        yield 'zero with prefix'  => ['Rp0', null];
    }

    public function testRejectsNonStringNonIntInput(): void
    {
        $this->assertNull(Rupiah::parse(null));
        $this->assertNull(Rupiah::parse(1.5));
        $this->assertNull(Rupiah::parse([]));
    }
}
