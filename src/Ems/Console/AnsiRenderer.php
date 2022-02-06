<?php
/**
 *  * Created by mtils on 02.02.2022 at 20:50.
 **/

namespace Ems\Console;

use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Core\Str;
use Ems\Contracts\View\View;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Patterns\ExtendableTrait;
use OutOfBoundsException;
use Traversable;
use UnexpectedValueException;

use function array_push;
use function is_array;
use function max;
use function mb_strlen;
use function str_pad;
use function str_repeat;
use function str_replace;
use function strip_tags;
use function strlen;

use const STR_PAD_RIGHT;

/**
 * This is a console renderer. You can use it as a helper to make console colors
 * by tags or format tables.
 * It is also used to "render" console views.
 * Just assign an extension (some callable) for a view and it will be used to
 * render this view.
 */
class AnsiRenderer implements Renderer, Extendable
{
    use ExtendableTrait;

    public const LINE_CONTENT_TYPE = 'text/x-console-lines';

    /**
     * @var array
     */
    private $tagStyles = [
        'info'      => '0;32;40',
        'mute'      => '1;30;40',
        'comment'   => '0;33;40',
        'warning'   => '1;33;40',
        'error'     => '1;37;41'
    ];

    /**
     * @var array
     */
    private $search;

    /**
     * @var
     */
    private $replace;

    /**
     * @param Renderable $item
     * @return bool
     */
    public function canRender(Renderable $item) : bool
    {
        if ($item instanceof View && $this->hasExtension($item->name())) {
            return true;
        }
        return $item instanceof Str && $item->mimeType() == self::LINE_CONTENT_TYPE;
    }

    public function render(Renderable $item)
    {
        if ($item instanceof Str) {
            return $this->format($item->getRaw());
        }
        if (!$item instanceof View) {
            throw new UnexpectedValueException('I only understand Str or View objects.');
        }
        try {
            return $this->callExtension($item->name(), [$item->name(), $item->assignments(), $this]);
        } catch (HandlerNotFoundException $e) {
            throw new OutOfBoundsException('No handler for template "' . $item->name() . '" found.', 0, $e);
        }
    }

    /**
     * Replace tags with ansi color codes.
     *
     * @param string $tagged
     * @return string
     */
    public function format(string $tagged) : string
    {
        return str_replace($this->search(), $this->replace(), $tagged);
    }

    /**
     * Remove the color tags.
     *
     * @param string $tagged
     * @return string
     */
    public function plain(string $tagged) : string
    {
        return strip_tags($tagged);
    }

    /**
     * @param array|Traversable $rows
     * @param array $header (optional)
     * @return string
     */
    public function table($rows, array $header=[]) : string
    {
        if (!$fieldLengths = $this->fieldLengths($rows, $header)) {
            return '';
        }

        $lines = [];
        $border = $this->tableBorder($fieldLengths);
        $lines[] = $border;
        $headerLine = '';

        if ($header) {
            $headerLine = $this->tableHeaderRow($header, $fieldLengths);
            $lines[] = $headerLine;
            $lines[] = $border;
        }

        if ($rows) {
            array_push($lines, ...$this->tableRows($rows, $fieldLengths));
        }

        if (!$rows && $headerLine) {
            $lines[] = $this->emptyTableRow($headerLine);
            $lines[] = "<mute>$border</mute>";
        } else {
            $lines[] = $border;
        }

        return implode("\n", $lines);

    }

    /**
     * @param string $tagged
     * @return int
     */
    public function plainLength(string $tagged) : int
    {
        return mb_strlen($this->plain($tagged));
    }

    /**
     * Get the console color style for a tag name.
     *
     * @param string $tag
     *
     * @return string
     */
    public function getTagStyle(string $tag) : string
    {
        return $this->tagStyles[$tag];
    }

    /**
     * Set a style (console color code) for a tag name
     *
     * @param string|array $tag
     * @param string|null  $style (optional)
     *
     * @return $this
     */
    public function setTagStyle($tag, string $style=null) : AnsiRenderer
    {
        if (!is_array($tag)) {
            $this->tagStyles[$tag] = $style;
            $this->search = null;
            $this->replace = null;
            return $this;
        }
        foreach ($tag as $name=>$style) {
            $this->setTagStyle($name, $style);
        }
        return $this;
    }

    /**
     * @return array
     */
    protected function search() : array
    {
        if (is_array($this->search)) {
            return $this->search;
        }
        $this->search = [];
        foreach ($this->tagStyles as $tag=>$style) {
            $this->search[] = "<$tag>";
            $this->search[] = "</$tag>";
        }
        return $this->search;
    }

    /**
     * @return array
     */
    protected function replace() : array
    {
        if (is_array($this->replace)) {
            return $this->replace;
        }
        $this->replace = [];
        foreach ($this->tagStyles as $tag=>$style) {
            $this->replace[] = "\e[$style" . 'm';
            $this->replace[] = "\e[0m";
        }
        return $this->replace;
    }

    /**
     * Create top and bottom lines of the table.
     *
     * @param array $fieldLengths
     * @param int $padding
     * @return string
     */
    protected function tableBorder(array $fieldLengths, int $padding=2) : string
    {
        $outer = '';
        foreach ($fieldLengths as $length) {
            $outer .= '+' . str_repeat('-', $length+$padding);
        }
        return "$outer+";
    }

    /**
     * Create the table header line.
     *
     * @param array $header
     * @param array $fieldLengths
     * @param string $style
     *
     * @return string
     */
    protected function tableHeaderRow(array $header, array $fieldLengths, string $style='comment') : string
    {
        $line = '';
        foreach ($header as $i=>$item) {
            $line .= "| <$style>" . str_pad($item, $fieldLengths[$i]+1, ' ', STR_PAD_RIGHT) . "</$style>";
        }
        return "$line|";
    }

    /**
     * Create a nice "the table is empty" row
     *
     * @param string $headerLine
     * @param string $style
     * @return string
     */
    protected function emptyTableRow(string $headerLine, string $style='mute') : string
    {
        $message = 'Table is empty';
        $borders = 2;
        $width = $this->plainLength($headerLine);
        $left = (int)round($width/2)-(strlen($message)/2) - $borders;
        $right = $width-$left-strlen($message) - $borders;
        return "<$style>|" . str_repeat(' ', $left) . $message . str_repeat(' ', $right) . "|</$style>";
    }

    /**
     * Format table rows.
     *
     * @param array $rows
     * @param array $fieldLengths
     * @return array
     */
    protected function tableRows(array $rows, array $fieldLengths) : array
    {
        $lines = [];
        foreach ($rows as $row) {
            $line = '';
            foreach ($row as $i=>$column) {
                $line .= str_pad("| $column", $fieldLengths[$i]+3, ' ', STR_PAD_RIGHT);
            }
            $lines[] = "$line|";
        }
        return $lines;
    }

    /**
     * @param array $rows
     * @param array $columns
     * @return int[]
     */
    protected function fieldLengths(array $rows, array $columns) : array
    {
        $fieldLengths= [];
        foreach ($rows as $row) {
            foreach ($row as $i=>$column) {
                if (!isset($fieldLengths[$i])) {
                    $fieldLengths[$i] = 0;
                }
                $fieldLengths[$i] = max($fieldLengths[$i], $this->plainLength($column));
            }
        }
        if (!$columns) {
            return $fieldLengths;
        }
        foreach ($columns as $i=>$column) {
            $fieldLengths[$i] = max($fieldLengths[$i] ?? 0, $this->plainLength($column));
        }
        return $fieldLengths;
    }
}