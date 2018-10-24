<?php

namespace Ems\Assets\Laravel;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Ems\Contracts\Assets\Registry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Ems\Contracts\Assets\NameAnalyser;
use Ems\Contracts\Core\Filesystem;
use Illuminate\Http\Response;

class AssetController extends Controller
{
    protected $registry;

    protected $names;

    protected $files;

    public function __construct(Registry $registry, Filesystem $files, NameAnalyser $names)
    {
        $this->registry = $registry;
        $this->files = $files;
        $this->names = $names;
    }

    public function show(Request $request)
    {
        $group = $this->getGroup($request);
        $file = $this->getFile($request);
        $path = $this->absolutePath($group, $file);
        $mimeType = $this->names->guessMimeType($file, $group);

        $content = $this->files->read($path);

        return new Response($content, 200, ['Content-Type' => $mimeType]);
    }

    protected function absolutePath($group, $file)
    {
        return $this->registry->to($group)->absolute($file);
    }

    protected function getGroup(Request $request)
    {
        if ($group = $request->get('group')) {
            return $group;
        }

        throw new BadRequestHttpException('Missing group parameter');
    }

    protected function getFile(Request $request)
    {
        if (!$file = $request->get('file')) {
            throw new BadRequestHttpException('Missing file parameter');
        }
        if (str_contains($file, '..')) {
            throw new BadRequestHttpException('Double dots are not allowed');
        }

        return ltrim($file, '/');
    }
}
