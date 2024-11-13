<?php

namespace Castor\Tests;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;
use Castor\Console\Command\TaskCommand;
use Castor\ContextRegistry;
use Castor\Descriptor\TaskDescriptor;
use Castor\ExpressionLanguage;
use Castor\Helper\Slugger;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AutocompleteTest extends TaskTestCase
{
    /** @dataProvider provideCompletionTests */
    public function testCompletion(\Closure $function, array $expectedValues, string $input = '')
    {
        $descriptor = new TaskDescriptor(new AsTask('task'), new \ReflectionFunction($function));

        $command = new TaskCommand(
            $descriptor,
            $this->createMock(ExpressionLanguage::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ContextRegistry::class),
            new Slugger(new AsciiSlugger()),
        );

        $tester = new CommandCompletionTester($command);
        $suggestions = $tester->complete([$input]);

        $this->assertSame($expectedValues, $suggestions);
    }

    public function provideCompletionTests(): \Generator
    {
        yield [task_with_static_autocomplete(...), ['a', 'b', 'c']];
        yield [task_with_autocomplete(...), ['d', 'e', 'f']];
        yield [task_with_autocomplete_filtered(...), ['foo', 'bar', 'baz']];
        yield [task_with_autocomplete_filtered(...), ['bar', 'baz'], 'ba'];
    }
}

function task_with_static_autocomplete(
    #[AsArgument(name: 'argument', autocomplete: ['a', 'b', 'c'])]
    string $argument,
): void {
}

function task_with_autocomplete(
    #[AsArgument(name: 'argument', autocomplete: 'Castor\Tests\complete')]
    string $argument,
): void {
}

/** @return string[] */
function complete(CompletionInput $input): array
{
    return [
        'd',
        'e',
        'f',
    ];
}

function task_with_autocomplete_filtered(
    #[AsArgument(name: 'argument', autocomplete: 'Castor\Tests\complete_filtered')]
    string $argument,
): void {
}

/** @return string[] */
function complete_filtered(CompletionInput $input): array
{
    return array_filter([
        'foo',
        'bar',
        'baz',
    ], fn (string $value) => str_starts_with($value, $input->getCompletionValue()));
}
