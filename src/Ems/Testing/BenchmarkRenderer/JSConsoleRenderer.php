<?php
/**
 *  * Created by mtils on 25.01.2022 at 21:58.
 **/

namespace Ems\Testing\BenchmarkRenderer;

use Ems\Skeleton\Application;
use Ems\Testing\Benchmark;

use function get_included_files;
use function implode;
use function json_encode;
use function strpos;

class JSConsoleRenderer
{
    public function render(Benchmark $benchmark) : string
    {
        $string = '<script>';
        foreach ($benchmark->getMarks() as $mark) {
            $string .= 'console.log("' . $mark['name'] . ' ' . Benchmark::milliSeconds($mark['duration']) . ' msecs (' . Benchmark::milliSeconds($mark['absolute']) . ' msecs)");' . "\n";
        }
        $string .= 'console.log("Total: ' . Benchmark::milliSeconds(Benchmark::totalDuration()) . ' msecs");' . "\n";

        $includedFiles = get_included_files();
        $includes = [];
        $countPerGroup = [
            'ems'    => 0,
            'app'    => 0,
            'vendor' => 0
        ];

        foreach ($includedFiles as $file) {

            $color = '#1a72ce';

            if (strpos($file, '/php-ems/')) {
                $color = '#903c85';
                $countPerGroup['ems']++;
            } elseif (strpos($file, '/vendor/')) {
                $color = '#555';
                $countPerGroup['vendor']++;
            } else {
                $countPerGroup['app']++;
            }
            $includes[] = "console.log('%c $file', 'color:$color');";
        }

        $string .= "function Lib(group, count) { this.group = group; this.count = count;}";

        $groups = [];
        foreach ($countPerGroup as $group=>$count) {
            $groups[] = "new Lib('$group', $count)";
        }

        $string .= 'console.table([' . implode(',', $groups) . "])\n";

        $string .= 'console.groupCollapsed("Includes");' . "\n";
        foreach ($includes as $include) {
            $string .= "$include\n";
        }
        $string .= "console.groupEnd();\n";
        $string .= "console.info('Config');\n";
        $string .= "console.dir(" . json_encode(Application::current()->getConfig()) . ")\n";

        $string .= '</script>';
        return $string;
    }
}