<?php

namespace Ems\Core;

use Ems\Contracts\Core\MimeTypeProvider;
use Ems\Core\Exceptions\ResourceNotFoundException;

class ManualMimeTypeProvider implements MimeTypeProvider
{
    /**
     * @var array
     **/
    protected $extensionsByType = [];

    /**
     * @var array
     **/
    protected $typeByExtension = [];

    /**
     * @var array
     **/
    protected $aliases = [];

    /**
     * @var bool
     **/
    protected $baseSetLoaded = false;

    /**
     * @var bool
     **/
    protected $extendedSetLoaded = false;

    /**
     * @var callable
     **/
    protected $extendedSetProvider;

    public function __construct()
    {
        $this->extendedSetProvider = function (ManualMimeTypeProvider $types) {
        };
    }

    /**
     * {@inheritdoc}
     *
     * @param string $fileName
     *
     * @return string
     **/
    public function typeOfName($fileName)
    {
        $this->loadBaseSetIfNotLoaded();
        $extension = $this->extensionOfName($fileName);

        if (isset($this->typeByExtension[$extension])) {
            return $this->typeByExtension[$extension];
        }

        if ($this->extendedSetLoaded) {
            return '';
        }

        $this->loadExtendedSet();

        $this->extendedSetLoaded = true;

        return $this->typeOfName($fileName);
    }

    /**
     * {@inheritdoc}
     *
     * @param $path
     *
     * @return string
     **/
    public function typeOfFile($path)
    {
        return $this->typeOfName($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $fileName
     * @param string $type     The awaited type
     * @param bool   $verbose  (optional) Check with typeOfFile
     *
     * @return bool
     **/
    public function isOfType($fileName, $type, $verbose = false)
    {
        if (!$mimeType = $verbose ? $this->typeOfFile($fileName) : $this->typeOfName($fileName)) {
            throw new ResourceNotFoundException("Mimetype of name '$fileName' not found");
        }

        $type = strtolower($type);

        // A little bit faster than the (uncommon) alias check
        if ($mimeType == $type) {
            return true;
        }

        // Check for aliases
        $realType = $this->realType($type);

        if ($mimeType == $realType) {
            return true;
        }

        $parts = explode('+', $mimeType);

        if (count($parts) == 1) {
            return false;
        }

        $derived = array_pop($parts);

        return $this->typeOfName($derived) == $realType;
    }

    /**
     * Fill the types by the passed ([$type] => ['ext1','ext2'].
     *
     * @param array
     *
     * @return self
     **/
    public function fillByArray(array $types)
    {
        foreach ($types as $mimeType => $extensions) {
            $this->extensionsByType[$mimeType] = $extensions;
            foreach ($extensions as $extension) {
                $this->typeByExtension[$extension] = $mimeType;
            }
        }

        return $this;
    }

    /**
     * Add an alias for a mimetype. application/html vs. text/html for example
     * Leads to a true in isOfType even if the type doesnt match otherwise.
     *
     * @param string $mimeTypeAlias
     * @param string $realType
     *
     * @return self
     **/
    public function alias($typeAlias, $realType)
    {
        $this->aliases[$typeAlias] = $realType;

        return $this;
    }

    /**
     * Returns the real type of the passed alias or if non found the alias itself.
     *
     * @param string $alias
     *
     * @return string
     **/
    public function realType($alias)
    {
        return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
    }

    /**
     * Assign a callable to provide an extended set of mimetypes (read apaches
     * mime.types, cache it,...
     *
     * @param callable $provider
     *
     * @return self
     **/
    public function provideExtendedSet(callable $provider)
    {
        $this->extendedSetProvider = $provider;

        return $this;
    }

    /**
     * Calculate the extension of fileName.
     *
     * @param string $fileName
     *
     * @return string
     **/
    protected function extensionOfName($fileName)
    {
        if (mb_strpos($fileName, '.') === false) {
            return mb_strtolower($fileName);
        }

        return mb_strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    /**
     * Loads the baseSet if not done before.
     */
    protected function loadBaseSetIfNotLoaded()
    {
        if ($this->baseSetLoaded) {
            return;
        }

        $this->fillByArray($this->baseSet);
        $this->registerBaseAliases();

        $this->baseSetLoaded = true;
    }

    /**
     * Calls the extended set provider.
     */
    protected function loadExtendedSet()
    {
        call_user_func($this->extendedSetProvider, $this);
    }

    /**
     * Some base aliases which are used all the time.
     **/
    protected function registerBaseAliases()
    {
        $this->alias('text/csv', 'text/comma-separated-values');
        $this->alias('text/tsv', 'text/tab-separated-values');
        $this->alias('text/javascript', 'application/javascript');
    }

    /**
     * Below for better readability of this class.
     *
     * @var array
     **/
    protected $baseSet = [
        'application/javascript' => ['js'],
        'application/json' => ['json'],
        'application/msexcel' => ['xls', 'xla'],
        'application/mspowerpoint' => ['ppt', 'ppz', 'pps', 'pot'],
        'application/msword' => ['doc', 'dot'],
        'application/octet-stream' => ['bin', 'exe', 'com', 'dll', 'class'],
        'application/pdf' => ['pdf'],
        'application/postscript' => ['ai', 'eps', 'ps'],
        'application/rtf' => ['rtf'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xslx'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/xhtml+xml' => ['xhtml'],
        'application/xml' => ['xml'],
        'application/x-httpd-php' => ['php', 'phtml', 'php4', 'php5', 'php3'],
        'application/x-shockwave-flash' => ['swf', 'cab'],
        'application/zip' => ['zip'],
        'image/gif' => ['gif'],
        'image/jpeg' => ['jpeg', 'jpg', 'jpe'],
        'image/png' => ['png'],
        'image/x-icon' => ['ico'],
        'text/comma-separated-values' => ['csv'],
        'text/css' => ['css'],
        'text/less+css' => ['less'],
        'text/sass+css' => ['scss', 'sass'],
        'text/html' => ['html', 'htm', 'shtml'],
        'text/plain' => ['txt'],
        'text/tab-separated-values' => ['tsv'],
        'video/mpeg' => ['mpeg', 'mpg', 'mpe'],
        'video/quicktime' => ['mov', 'qt'],
        'video/x-msvideo' => ['avi'],

    ];
}
