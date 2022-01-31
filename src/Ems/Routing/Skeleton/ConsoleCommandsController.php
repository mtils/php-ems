<?php
/**
 *  * Created by mtils on 31.01.2022 at 21:02.
 **/

namespace Ems\Routing\Skeleton;


use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\ConsoleParameter;
use Ems\Contracts\Routing\Exceptions\RouteNotFoundException;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\Router;
use Ems\Core\Helper;
use Ems\Routing\ArgvInput;
use Ems\Skeleton\Connection\ConsoleOutputConnection;

use function fnmatch;
use function implode;
use function in_array;
use function max;
use function str_pad;
use function str_repeat;
use function strlen;
use function trim;

class ConsoleCommandsController
{
    /**
     * Show all console commands.
     *
     * @param Input $input
     * @param Router $router
     * @param ConsoleOutputConnection $out
     */
    public function index(
        Input $input,
        Router $router,
        ConsoleOutputConnection $out
    ) {
        $commands = $this->getConsoleCommands($router);
        $maxLength = $this->longestCommandLength($commands);
        $padLength = $maxLength + 8;
        $pattern = trim($input->get('pattern', ''));

        foreach ($commands as $command) {
            if ($pattern && !$this->matches($command->pattern, $pattern)) {
                continue;
            }
            $out->line(
                '<info>' . str_pad(
                    $command->pattern,
                    $padLength
                ) . '</info>' . $command->description
            );
        }
    }

    /**
     * Show help for a single command.
     *
     * @param ArgvInput $input
     * @param Router $router
     * @param ConsoleOutputConnection $out
     */
    public function show(
        ArgvInput $input,
        Router $router,
        ConsoleOutputConnection $out
    ) {
        $command = $input->argument('command_name');
        $commands = $this->getConsoleCommands($router);

        $indentSize = 2;
        $indent = str_repeat(' ', $indentSize);

        if (!isset($commands[$command])) {
            throw new RouteNotFoundException("Command $command not found.");
        }

        $command = $commands[$command];
        $inlineHelp = [];

        if ($command->options) {
            $inlineHelp[] = $out->format('<comment>[options]</comment>');
        }

        if ($argLine = $this->buildArgumentLine($command, $out)) {
            $inlineHelp[] = implode(' ', $argLine);
        }

        $inlineHelpString = $inlineHelp ? ' ' . implode(
                ' ',
                $inlineHelp
            ) . ' ' : ' ';

        $out->line('<comment>Usage:</comment>');

        $out->line(
            "$indent<info>$command->pattern</info>$inlineHelpString<mute>$command->description</mute>"
        );

        if ($argLine) {
            $this->printArgumentHelp($command, $out, $indent);
        }

        if ($command->options) {
            $this->printOptionHelp($command, $out, $indent);
        }
    }

    /**
     * @param Router $router
     *
     * @return Command[]
     */
    protected function getConsoleCommands(Router $router): array
    {
        $consoleCommands = [];

        /** @var Route $route */
        foreach ($router as $route) {
            if (in_array(
                    Input::CLIENT_CONSOLE,
                    $route->clientTypes
                ) && $route->command) {
                $consoleCommands[$route->command->pattern] = $route->command;
            }
        }

        return $consoleCommands;
    }

    /**
     * @param Command[] $commands
     *
     * @return int
     */
    protected function longestCommandLength($commands): int
    {
        $max = 0;
        foreach ($commands as $command) {
            $max = max($max, strlen($command->pattern));
        }
        return (int)$max;
    }

    /**
     * @param ConsoleParameter[] $parameters
     *
     * @return int
     */
    protected function longestParameterLength($parameters): int
    {
        $max = 0;
        foreach ($parameters as $parameter) {
            $max = max($max, strlen($parameter->name));
        }
        return (int)$max;
    }

    /**
     * @param Command $command
     * @param ConsoleOutputConnection $out
     *
     * @return array
     */
    protected function buildArgumentLine(
        Command $command,
        ConsoleOutputConnection $out
    ): array {
        $argLine = [];

        foreach ($command->arguments as $argument) {
            // Hide the first argument
            if ($argument->name == 'command') {
                continue;
            }
            $argLine[] = $argument->required ? "<$argument->name>" : $out->format(
                "<mute>[<$argument->name>]</mute>"
            );
        }

        return $argLine;
    }

    /**
     * @param Command $command
     * @param ConsoleOutputConnection $out
     * @param string $indent
     */
    protected function printArgumentHelp(
        Command $command,
        ConsoleOutputConnection $out,
        string $indent
    ) {
        $out->line('');
        $out->line('<comment>Arguments:</comment>');

        $longestArg = $this->longestParameterLength($command->arguments);
        $padLength = $longestArg + 6;
        foreach ($command->arguments as $argument) {
            // Hide the first argument
            if ($argument->name == 'command') {
                continue;
            }
            $out->line(
                $indent . str_pad(
                    $argument->name,
                    $padLength
                ) . " <mute>$argument->description</mute>"
            );
        }
    }

    /**
     * @param Command $command
     * @param ConsoleOutputConnection $out
     * @param string $indent
     */
    protected function printOptionHelp(
        Command $command,
        ConsoleOutputConnection $out,
        string $indent
    ) {
        $out->line('');
        $out->line('<comment>Options:</comment>');

        $longestArg = $this->longestParameterLength($command->arguments);
        $padLength = $longestArg + 6;
        foreach ($command->options as $option) {
            $out->line(
                $indent . str_pad(
                    $option->name,
                    $padLength
                ) . " <mute>$option->description</mute>"
            );
        }
    }

    /**
     * @param string $commandName
     * @param string $pattern
     *
     * @return bool
     */
    protected function matches(string $commandName, string $pattern): bool
    {
        if (Helper::startsWith($commandName, $pattern)) {
            return true;
        }
        return fnmatch($pattern, $commandName);
    }
}