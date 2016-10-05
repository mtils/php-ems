<?php


namespace Ems\Assets\Parser;


use Ems\Contracts\Core\TextParser;
use Ems\Contracts\Core\Filesystem;
use InvalidArgumentException;
use Ems\Core\AppPath as DefaultAppPath;
use Ems\Contracts\Core\AppPath;
use RuntimeException;
use CssParser;
use CssUrlParserPlugin;
use CssRulesetDeclarationToken as CssToken;


class CssUrlReplaceParser implements TextParser
{

    protected $defaultOptions = [
    ];

    /**
     * @var \Ems\Contracts\Core\Filesystem
     **/
    protected $files;

    /**
     * @var \Ems\Contracts\Core\AppPath
     **/
    protected $mapper;

    /**
     * @var callable
     **/
    protected $parserCreator;

    /**
     * @param \Ems\Contracts\Core\Filesystem $files
     * @param \Ems\Contracts\Core\AppPath $appPath (optional)
     * @param callable $parserCreator (optional)
     **/
    public function __construct(Filesystem $files, AppPath $appPath=null, callable $parserCreator=null)
    {
        $this->files = $files;
        $this->parserCreator = $parserCreator ?: function ($text) {
            return new CssParser($text);
        };

//         if (!$appPath) {
            $appPath = (new DefaultAppPath)->enableFilesystemChecks(true);
//         }

        $this->mapper = $appPath;

    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @param array $config The configuration options
     * @param bool $purgePlaceholders (optional)
     * @return string
     **/
    public function parse($text, array $config, $purgePlaceholders=true)
    {

        $parser = call_user_func($this->parserCreator, $text);

        list($infile, $outfile) = $this->inFileAndOutFile($config);

        $tokens = $parser->getTokens();

        $string = '';

        foreach ($tokens as $token) {
            if ($token instanceof CssToken) {
                $this->replaceWithCorrectedPath($token, $infile, $outfile);
            }
            $string .= "$token";
        }

        return $string;

    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @return string The purged text
     **/
    public function purge($text)
    {
        return $text;
    }

    /**
     * Merges the passed options with the default option
     *
     * @param array $passedOptions
     * @return arras
     **/
    protected function mergeOptions(array $passedOptions)
    {
        return array_merge($this->defaultOptions, $passedOptions);
    }

    protected function replaceWithCorrectedPath(CssToken $token, $infile, $outfile)
    {

        $tokenValue = $token->Value;

        $matches = [];


        if (!preg_match('/url\(\s*([\'"]*)(?P<file>[^\1]+)\1\s*\)/i', $tokenValue, $matches)) {
            return;
        }

        if (!isset($matches['file'])) {
            return;
        }

        $originalPath = $matches['file'];

        if ($this->isAbsoluteUrl($originalPath) || $this->isDataUrl($originalPath)) {
            return;
        }

        $inDir = $this->files->dirname($infile);
        $outDir = $this->files->dirname($outfile);

        $absoluteReferencedFile = $this->absoluteOriginalPath($inDir, $originalPath);

        $relativePath = $this->absoluteToRelative($outDir, $absoluteReferencedFile);

        $newAbsolutePath = $this->existingAbsolutePath($outDir, $relativePath);


        $token->Value = str_replace($originalPath, $relativePath, $tokenValue);

    }

    protected function inFileAndOutFile(array $config)
    {

        if (!isset($config['file_path']) || !$config['file_path']) {
            throw new InvalidArgumentException('Config misses "file_path"');
        }
        if (!isset($config['target_path']) || !$config['target_path']) {
            throw new InvalidArgumentException('Config misses "target_path"');
        }

        return [$config['file_path'], $config['target_path']];

    }

    protected function absoluteToRelative($baseDir, $absolutePath)
    {

        $this->mapper->setBasePath($baseDir);

        $relativePath = $this->mapper->relative($absolutePath);

        if ($relativePath == '.') {
            throw new RuntimeException("Mapper cant find relative path of $outDir/$includedFilePath");
        }

        return $relativePath;
    }

    protected function absoluteOriginalPath($inDir, $originalPath)
    {
        $includedFile = $inDir . "/" . ltrim($originalPath,'/');

        // TODO: Not injectable direct use of php function
        if (!$absolutePath = realpath($includedFile)) {
            throw new RuntimeException("Absolute path of $includedFile not found");
        }

        return $absolutePath;
    }

    protected function existingAbsolutePath($outDir, $relativePath)
    {
        $newAbsolutePath = "$outDir/$relativePath";

        if (!$this->files->exists($newAbsolutePath)) {
            throw new RuntimeException("Mapped css path $newAbsolutePath does not exist");
        }

        return $newAbsolutePath;
    }

    protected function newPathExists($outDir, $newPath)
    {
        $outFile = "$outDir/$newPath";
        return $this->files->exists($outFile);
    }

    protected function isAbsoluteUrl($url)
    {
        return (strpos($url, 'http://') === 0 ||
                strpos($url, 'https://') === 0 ||
                strpos($url, '/') === 0);
    }

    protected function isDataUrl($url)
    {
        return (strpos($url, 'data:') === 0);
    }

}
