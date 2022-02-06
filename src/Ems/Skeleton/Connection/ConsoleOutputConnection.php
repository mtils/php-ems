<?php
/**
 *  * Created by mtils on 03.10.19 at 06:56.
 **/

namespace Ems\Skeleton\Connection;


use Ems\Console\AnsiRenderer;
use Ems\Contracts\Core\Stringable;
use Ems\Core\Response;

use function is_bool;

use const PHP_EOL;

class ConsoleOutputConnection extends StdOutputConnection
{

    /**
     * @var AnsiRenderer
     */
    private $renderer;

    /**
     * @var bool
     */
    private $formattedOutput = true;

    public function __construct(...$args)
    {
        $this->renderer = new AnsiRenderer();
        parent::__construct(...$args);
    }


    /**
     * Output a line. Replace any tags with console color styles.
     *
     * @param string $output
     * @param bool   $formatted (optional)
     * @param string $newLine (default: PHP_EOL)
     */
    public function line(string $output, bool $formatted=null, string $newLine=PHP_EOL)
    {
        $formatted = is_bool($formatted) ? $formatted : $this->shouldFormatOutput();
        $output = $formatted ? $this->renderer->format($output) : $this->renderer->plain($output);
        $this->write($output . $newLine);
    }

    /**
     * Returns true when the tags should be colored. (Otherwise they get removed)
     *
     * @return bool
     */
    public function shouldFormatOutput() : bool
    {
        return $this->formattedOutput;
    }

    /**
     * @param $output
     * @param bool $lock
     * @return bool|mixed|void|null
     */
    public function write($output, bool $lock = false)
    {
        if (!$output instanceof Response) {
            return parent::write($output, $lock);
        }
        $payload = $output->payload;
        $stringPayload = $payload instanceof Stringable ? $payload->toString() : "$payload";
        if ($output->contentType != AnsiRenderer::LINE_CONTENT_TYPE) {
            return parent::write($stringPayload, $lock);
        }
        $lines = explode(PHP_EOL, $stringPayload);

        foreach ($lines as $line) {
            $this->line($line);
        }
    }

}