#!/usr/bin/env php
<?php

include __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client as HttpClient;
use MKraemer\GarminConnect\GarminSSO;
use MKraemer\GarminConnect\ActivityUploader;

$console = new Application();
$console
    ->register('upload')
    ->setDefinition(array(
        new InputArgument('user', InputArgument::REQUIRED, 'Username'),
        new InputArgument('pass', InputArgument::REQUIRED, 'Password'),
        new InputArgument('dir', InputArgument::REQUIRED, 'Directory name'),
        new InputOption('delete', null, InputOption::VALUE_NONE, 'Delete previously uploaded files')
    ))
    ->setDescription('Uploads the .fit files in the given directory')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $username  = $input->getArgument('user');
        $password  = $input->getArgument('pass');
        $directory = $input->getArgument('dir');

        $output->writeln(sprintf('<info>Signing in as user %s</info>', $username));

        $client = new HttpClient();

        $sso = new GarminSSO(
            $client,
            $username,
            $password
        );

        $cookieJar = $sso();

        $output->writeln(sprintf('<info>Signed in as user %s</info>', $username));

        $activityUploader = new ActivityUploader($client, $cookieJar);

        $directoryIterator = new DirectoryIterator($directory);

        foreach ($directoryIterator as $fileInfo) {
            if (!$fileInfo->isDot()) {
                $output->writeln(sprintf('Uploading %s', $fileInfo->getFilename()));
                $result = $activityUploader($fileInfo);

                if ($result === ActivityUploader::RESULT_UPLOAD_SUCCESSFUL) {
                    $output->writeln(sprintf('<info>%s successfully uploaded</info>', $fileInfo->getFilename()));
                } elseif ($result === ActivityUploader::RESULT_UPLOAD_DUPLICATE) {
                    $output->writeln(sprintf('<comment>%s was uploaded previously</comment>', $fileInfo->getFilename()));
                    if ($input->getOption('delete')) {
                        unlink ($fileInfo->getRealPath());
                        $output->writeln(sprintf('<info>Deleted %s</info>', $fileInfo->getFilename()));
                    }
                }
            }
        }
    })
    ;
$console->run();
