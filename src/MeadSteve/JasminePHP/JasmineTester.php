<?php
namespace MeadSteve\JasminePHP;

/**
 * Used to build a test runner for a series of js files.
 *
 */
class JasmineTester
{

    protected $arrJSSources = array();
    protected $arrJSSpecs = array();

    protected $rootDir;

    protected $localFilePattern;
    protected $remoteFilePattern;

    public function __construct($RootDir = "")
    {
        $this->rootDir = $RootDir;
    }

    /**
     *
     * @param string $jSPath    The path of the JS file to test.
     * @param string $specPath  The path of the tests for the above js file.
     * @return JasmineTester    This object (fluent)
     * @throws \InvalidArgumentException If either arg is not a string.
     */
    public function registerJS($jSPath, $specPath)
    {
        $this->registerJSPath($jSPath);
        $this->registerJSSpecPath($specPath);
        return $this;
    }

    public function registerJSPath($jSPath) {
        if (!is_string($jSPath)) {
            throw new \InvalidArgumentException('JSPath must be a string');
        }
        $this->arrJSSources[] = $jSPath;
        return $this;
    }

    public function registerJSSpecPath($specPath) {
        if (!is_string($specPath)) {
            throw new \InvalidArgumentException('SpecPath must be a string');
        }
        $this->arrJSSpecs[] = $specPath;
        return $this;
    }

    public function addJSDir($path, $pattern = ".*") {
        $that = $this;
        $this->callOverPath($path, $pattern, function($path) use ($that) {
                $this->registerJSPath($path);
            });
    }

    public function addJSSpecDir($path, $pattern = ".*") {
        $that = $this;
        $this->callOverPath($path, $pattern, function($path) use ($that) {
                $this->registerJSSpecPath($path);
            });
    }

    /**
     * @param string $localPathPattern     A regular expression that matches the
     *                                     local filepaths.
     * @param string $externalPath         The path to replace the above with when
     *                                     rendering. $1, $GroupName match groups
     *                                     in the regex.
     * @return JasmineTester    This object (fluent)
     * @throws \InvalidArgumentException If either arg is not a string.
     */
    public function setPathTranslation($localPathPattern, $externalPath)
    {
        $this->localFilePattern = $localPathPattern;
        $this->remoteFilePattern = $externalPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getJSIncludeHTML()
    {
        $output = "";
        $arrSources = array_map(array($this, "getExternalFilePath"),
                                $this->arrJSSources);
        foreach ($arrSources as $path) {
            $output .= sprintf(
                '<script type="text/javascript" src="%s"></script>',
                $path
            );
            $output .= PHP_EOL;
        }
        return $output;
    }

    /**
     * @return string
     */
    public function getJSSpecIncludeHTML()
    {
        $Output = "";
        $arrSpecs = array_map(array($this, "getExternalFilePath"),
                        $this->arrJSSpecs);
        foreach ($arrSpecs as $Path) {
            $Output .= sprintf(
                '<script type="text/javascript" src="%s"></script>',
                $Path
            );
            $Output .= "" . PHP_EOL;
        }
        return $Output;
    }

    public function __toString()
    {
        return $this->getHeaderHTML()
            . PHP_EOL
            . $this->getJSIncludeHTML()
            . PHP_EOL
            . $this->getJSSpecIncludeHTML()
            . PHP_EOL
            . $this->getFooterHTML();
    }

    protected function getExternalFilePath($internalPath) {
        if ($this->localFilePattern === null) {
            return $internalPath;
        }
        else {
            $arrParts = array();
            preg_match(
                "#" . $this->localFilePattern . "#s",
                $internalPath,
                $arrParts
            );
            $newPath = $this->remoteFilePattern;
            foreach($arrParts as $PatternName => $Value) {
                $newPath = str_replace('$' . $PatternName, $Value, $newPath);
            }

            return $newPath;
        }
    }

    protected function getHeaderHTML()
    {
        $headerTemplate = file_get_contents(__DIR__ . "/Header.inc");
        return str_replace('{{RootPath}}', $this->rootDir, $headerTemplate);
    }

    protected function getFooterHTML()
    {
        return file_get_contents(__DIR__ . "/Footer.inc");
    }

    protected function callOverPath ($path, $pattern, $callable) {
        // We want to search all sub folders of maintenance/tests/unit_tests for
        // files that match our regex.
		$recursedDirs = new \RecursiveDirectoryIterator($path);
		$flattenedFiles = new \RecursiveIteratorIterator($recursedDirs);
		$filteredFiles = new \RegexIterator($flattenedFiles,
										   $pattern,
										   $pattern,
										   \RecursiveRegexIterator::GET_MATCH);
        foreach ($filteredFiles as $FilePath => $Match) {
            call_user_func_array($callable, array($FilePath, $Match));
		}
    }
}
