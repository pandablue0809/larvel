<?php

namespace LaravelEasyRepository;

use Carbon\Laravel\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use LaravelEasyRepository\helpers\Search;
use SplFileInfo;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * File
     *
     * @property $files
     */
    private Filesystem $files;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->files = $this->app->make(Filesystem::class);
        if ($this->isConfigPublished()) {
            $this->bindAllRepositories();
            $this->bindAllServices();
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Loop through the repository interfaces and bind each interface to its
     * Repository inside the implementations
     *
     * @return void
     */
    private function bindAllRepositories()
    {
        $repositoryInterfaces = $this->getRepositoryPath();

        foreach ($repositoryInterfaces as $key => $repositoryInterface) {
            $repositoryInterfaceClass =  config("easy-repository.repository_namespace"). "\\"
                                        . $repositoryInterface. "\\"
                                        . $repositoryInterface
                                        . config("easy-repository.repository_interface_suffix");

            $repositoryImplementClass = config("easy-repository.repository_namespace"). "\\"
                                        . $repositoryInterface. "\\"
                                        . $repositoryInterface
                                        . config("easy-repository.repository_suffix");

            $this->app->bind($repositoryInterfaceClass, $repositoryImplementClass);
        }
    }

    /**
     * bind all service
     */
    private function bindAllServices() {
        $servicePath = $this->getServicePath();

        foreach ($servicePath as $serviceName) {
            $splitname = explode("/", $serviceName);
            $className = end($splitname);

            $pathService = str_replace("/", "\\", $serviceName);

            $serviceInterfaceClass =  config("easy-repository.service_namespace"). "\\"
                . $pathService. "\\"
                .$className
                .config("easy-repository.service_interface_suffix");

            $serviceImplementClass = config("easy-repository.service_namespace"). "\\"
                . $pathService. "\\"
                .$className
                .config("easy-repository.service_suffix");

            $this->app->bind($serviceInterfaceClass, $serviceImplementClass);
        }
    }

    /**
     * Check inside the repositories interfaces directory and get all interfaces
     *
     * @return Collection
     */
    public function getRepository()
    {
        $interfaces = collect([]);
        $directory = $this->getRepositoryPath();
        $files = $this->files->files($directory);
        if (is_array($files)) {
            $interfaces = collect($files)->map(function (SplFileInfo $file) {
                return str_replace(".php", "", $file->getFilename());
            });
        }

        return $interfaces;
    }

    /**
     * Get repositories path
     *
     * @return array
     */
    private function getRepositoryPath()
    {
        $folders = [];
        if(file_exists($this->app->basePath() . "/" . config("easy-repository.repository_directory"))) {
            $dirs = File::directories($this->app->basePath() .
                "/" . config("easy-repository.repository_directory"));
            foreach ($dirs as $dir) {
                $dir = str_replace('\\', '/', $dir);
                $arr = explode("/", $dir);

                $folders[] = end($arr);
            }
        } else {

        }


        return $folders;
    }

    /**
     * Get repository interface namespace
     *
     * @return string
     */
    private function getRepositoryInterfaceNamespace(string $className)
    {
        return config("easy-repository.repository_namespace") . "\\".$className."\\";
    }

    /**
     * Get repository namespace
     *
     * @return string
     */
    private function getRepositoryNamespace(string $className)
    {
        return config("easy-repository.repository_namespace") .
            "\\" . $className;
    }

    /**
     * Get repository file name
     *
     * @return string
     */
    private function getRepositoryFileName($className)
    {
        return $className . config("easy-repository.repository_suffix");
    }

    /**
     * Get repository names
     *
     * @return Collection
     */
    private function getRepositoryFiles()
    {
        $repositories = collect([]);
        $repositoryDirectory = $this->getRepositoryPath();
        $files = $this->files->files($repositoryDirectory);
        if (is_array($files)) {
            $repositories = collect($files)->map(function (SplFileInfo $file) {
                return str_replace(".php", "", $file->getFilename());
            });
        }

        return $repositories;
    }

    /**
     * get service path
     * @return array
     */
    private function getServicePath() {
        $root = $this->app->basePath() .
            "/" . config("easy-repository.service_directory");
        $servicePath = [];

        if(file_exists($root)) {
            $path = Search::file($root, ["php"]);


            foreach ($path as $file) {
                $file_path = strstr($file->getPath(), "Services");
                $file_path = str_replace('\\', '/', $file_path);
                $servicePath[] = str_replace("Services/","",$file_path);
            }
        }

        return array_unique($servicePath);
    }

    /**
     * Check if config is published
     *
     * @return bool
     */
    private function isConfigPublished()
    {
        $path = config_path("easy-repository.php");
        $exists = file_exists($path);

        return $exists;
    }
}
