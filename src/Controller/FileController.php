<?php

namespace SwagTestEnvironment\Controller;

use Shopware\Core\Framework\Routing\RoutingException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ["_routeScope" => ["administration"]])]
class FileController
{
    private string $baseFolder;

    public function __construct(string $baseFolder)
    {
        $this->baseFolder = $baseFolder . '/custom/plugins/';
    }

    #[Route(path: "/api/_action/code-editor/files", methods: ["GET"])]
    public function getFiles(): JsonResponse
    {
        $finder = new Finder();
        $finder = $finder
            ->in($this->baseFolder)
            ->files()
            ->notPath(['/SwagTestEnvironment/i', '/FroshTools/i', '/FroshPlatformAdminer/i', '/SwagLanguagePack/i', '/node_modules/i']);

        $files = [];

        foreach ($finder->getIterator() as $file) {
            $files[] = [
                'name' => $this->stripPath($file->getPathname())
            ];
        }

        return new JsonResponse($files);
    }

    #[Route(path: "/api/_action/code-editor/file", methods: ["GET"])] public function getFile(Request $request): Response
    {
        $content = '';

        $newPath = $this->getPathOfRequest($request);

        if (file_exists($newPath)) {
            $content = file_get_contents($newPath);
        }

        return new JsonResponse(['content' => $content]);
    }

    #[Route(path: "/api/_action/code-editor/file", methods: ["PUT"])]
    public function saveFile(Request $request): Response
    {
        $newPath = $this->getPathOfRequest($request);

        file_put_contents($newPath, $request->request->get('content'));

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return new Response('', Response::HTTP_OK);
    }

    private function stripPath(string $path): string
    {
        if (str_starts_with($path, $this->baseFolder)) {
            return substr($path, strlen($this->baseFolder));
        }

        return $path;
    }

    private function getPathOfRequest(Request $request): string
    {
        $file = $request->query->get('file', '');

        if ($file === '') {
            throw RoutingException::missingRequestParameter('file');
        }

        $baseDir = realpath($this->baseFolder);
        $newPath = realpath($this->baseFolder . '/' . $file);

        if (!str_starts_with($newPath, $baseDir)) {
            throw new \InvalidArgumentException('Path travel detected');
        }

        return $newPath;
    }
}
