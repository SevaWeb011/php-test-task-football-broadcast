<?php

namespace App\Service;

use App\Entity\Match;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class HtmlSaver
{
    private string $resultDir;
    private Environment $twig;

    public function __construct(string $templateDir, string $resultDir)
    {
        $loader = new FilesystemLoader($templateDir);
        $this->twig = new Environment($loader);

        $this->resultDir = rtrim($resultDir, DIRECTORY_SEPARATOR);
    }

    public function save(Match $match): string
    {
        $content =  $this->twig->render('match.html.twig', [
            'match' => $match,
            'statsTeamHome' => $match->getPositonStatsForTeamHome(),
            'statsTeamAway' => $match->getPositonStatsForTeamAway(),
            'message' => getenv('MESSAGE') ?? ''
        ]);

        $path = $this->buildPath($match);

        file_put_contents($path, $content);

        return $path;
    }

    private function buildPath(Match $match): string
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [
                $this->resultDir,
                "{$match->getId()}.html"
            ]
        );
    }
}