<?php

namespace Nexus\Install;

use http\Exception\InvalidArgumentException;
use Nexus\Database\DB;

class Install
{
    private $currentStep;

    private $minimumPhpVersion = '7.2.0';

    protected $steps = ['环境检测', '添加 .env 文件', '新建数据表', '导入数据', '创建管理员账号'];


    public function __construct()
    {
        $this->currentStep = min(intval($_REQUEST['step'] ?? 1) ?: 1, count($this->steps) + 1);
    }

    public function currentStep()
    {
        return $this->currentStep;
    }

    public function getLogFile()
    {
        return sprintf('%s/nexus_install_%s.log', sys_get_temp_dir(), date('Ymd'));
    }

    public function getInsallDirectory()
    {
        return ROOT_PATH . 'public/install';
    }

    public function doLog($log)
    {
        $log = sprintf('[%s] [%s] %s%s', date('Y-m-d H:i:s'), $this->currentStep, $log, PHP_EOL);
        file_put_contents($this->getLogFile(), $log, FILE_APPEND);
    }

    public function listAllTableCreate($sqlFile = '')
    {
        if (empty($sqlFile)) {
            $sqlFile = ROOT_PATH . '_db/dbstructure_v1.6.sql';
        }
        $pattern = '/CREATE TABLE `(.*)`.*;/isU';
        $string = file_get_contents($sqlFile);
        if ($string === false) {
            throw new \RuntimeException("sql file: $sqlFile can not read, make sure it exits and can be read.");
        }
        $count = preg_match_all($pattern, $string, $matches, PREG_SET_ORDER);
        if ($count == 0) {
            return [];
        }
        return array_column($matches, 0, 1);
    }

    public function listExistsTable()
    {
        dbconn(false, false);
        $sql = 'show tables';
        $res = sql_query($sql);
        $data = [];
        while ($row = mysql_fetch_row($res)) {
            $data[] = $row[0];
        }
        return $data;
    }

    public function listShouldAlterTableTableRows()
    {
        $tables = $this->listExistsTable();
        $data = [];
        foreach ($tables as $table) {
            $sql = "desc $table";
            $res = sql_query($sql);
            while ($row = mysql_fetch_assoc($res)) {
                if ($row['Type'] == 'datetime' && $row['Default'] == '0000-00-00 00:00:00') {
                    $data[$table][] = $row['Field'];
                    $data[] = [
                        'label' => "$table." . $row['Field'],
                        'required' => 'default null',
                        'current' => '0000-00-00 00:00:00',
                        'result' => 'NO',
                    ];
                }
            }
        }
        return $data;
    }

    public function listRequirementTableRows()
    {
        $gdInfo = gd_info();
        $tableRows = [
            [
                'label' => 'PHP version',
                'required' => '>= ' . $this->minimumPhpVersion,
                'current' => PHP_VERSION,
                'result' => $this->yesOrNo(version_compare(PHP_VERSION, $this->minimumPhpVersion, '>=')),
            ],
            [
                'label' => 'PHP extension redis',
                'required' => 'optional',
                'current' => extension_loaded('redis'),
                'result' => $this->yesOrNo(extension_loaded('redis')),
            ],
            [
                'label' => 'PHP extension mysqli',
                'required' => 'enabled',
                'current' => extension_loaded('mysqli'),
                'result' => $this->yesOrNo(extension_loaded('mysqli')),
            ],
            [
                'label' => 'PHP extension mbstring',
                'required' => 'enabled',
                'current' => extension_loaded('mbstring'),
                'result' => $this->yesOrNo(extension_loaded('mbstring')),
            ],
            [
                'label' => 'PHP extension gd',
                'required' => 'enabled',
                'current' => extension_loaded('gd'),
                'result' => $this->yesOrNo(extension_loaded('gd')),
            ],
            [
                'label' => 'PHP extension gd JPEG Support',
                'required' => 'true',
                'current' => $gdInfo['JPEG Support'],
                'result' => $this->yesOrNo($gdInfo['JPEG Support']),
            ],
            [
                'label' => 'PHP extension gd PNG Support',
                'required' => 'true',
                'current' => $gdInfo['PNG Support'],
                'result' => $this->yesOrNo($gdInfo['PNG Support']),
            ],
            [
                'label' => 'PHP extension gd GIF Read Support',
                'required' => 'true',
                'current' => $gdInfo['GIF Read Support'],
                'result' => $this->yesOrNo($gdInfo['GIF Read Support']),
            ],
        ];
        $fails = array_filter($tableRows, function ($value) {return $value['required'] == 'true' && $value['result'] == 'NO';});
        $pass = empty($fails);

        return [
            'table_rows' => $tableRows,
            'pass' => $pass,
        ];
    }

    public function listSettingTableRows()
    {
        $defaultSettingsFile = ROOT_PATH . '_doc/install/settings.default.php';
        $originalConfigFile = ROOT_PATH . 'config/allconfig.php';
        if (!file_exists($defaultSettingsFile)) {
            throw new \RuntimeException("default setting file: $defaultSettingsFile not exists.");
        }
        if (!file_exists($originalConfigFile)) {
            throw new \RuntimeException("original setting file: $originalConfigFile not exists.");
        }
        $tableRows = [
            [
                'label' => basename($defaultSettingsFile),
                'required' => 'exists && readable',
                'current' => $defaultSettingsFile,
                'result' =>  $this->yesOrNo(file_exists($defaultSettingsFile) && is_readable($defaultSettingsFile)),
            ],
            [
                'label' => basename($originalConfigFile),
                'required' => 'exists && readable',
                'current' => $originalConfigFile,
                'result' =>  $this->yesOrNo(file_exists($originalConfigFile) && is_readable($originalConfigFile)),
            ],
        ];
        $requireDirs = [
            'main' => ['bitbucket', 'torrent_dir'],
            'attachment' => ['savedirectory', ],
        ];
        $symbolicLinks = [];
        require $originalConfigFile;
        $settings = require $defaultSettingsFile;
        foreach ($settings as $prefix => &$group) {
            $prefixUpperCase = strtoupper($prefix);
            $oldGroupValues = $$prefixUpperCase ?? null;
            foreach ($group as $key => &$value) {
                //merge original config to default setting
                if (isset($oldGroupValues) && isset($oldGroupValues[$key])) {
                    $value = $oldGroupValues[$key];;
                }
                if (isset($requireDirs[$prefix]) && in_array($key, $requireDirs[$prefix])) {
                    $dir = getFullDirectory($value);
                    $tableRows[] = [
                        'label' => "{$prefix}.{$key}",
                        'required' => 'exists && readable',
                        'current' => $dir,
                        'result' => $this->yesOrNo(is_dir($dir) && is_readable($dir)),
                    ];
                    $symbolicLinks[] = $dir;
                }
            }
        }
        $fails = array_filter($tableRows, function ($value) {return $value['required'] == 'true' && $value['result'] == 'NO';});
        $pass = empty($fails);
        return [
            'table_rows' => $tableRows,
            'symbolic_links' => $symbolicLinks,
            'settings' => $settings,
            'pass' => $pass,
        ];
    }

    public function nextStep()
    {
        $this->gotoStep($this->currentStep + 1);
    }

    public function gotoStep($step)
    {
        header('Location: ' . getBaseUrl() . "?step=$step");
        exit(0);
    }

    public function maxStep()
    {
        return count($this->steps);
    }

    public function yesOrNo($condition) {
        if ($condition) {
            return 'YES';
        }
        return 'NO';
    }

    public function renderTable($header, $data)
    {
        $table = '<div class="table w-full text-left">';
        $table .= '<div class="table-row-group">';
        $table .= '<div class="table-row">';
        foreach ($header as $value) {
            $table .= '<div class="table-cell bg-gray-400 text-gray-700 px-4 py-2">' . $value . '</div>';
        }
        $table .= '</div>';
        foreach ($data as $value) {
            $table .= '<div class="table-row">';
            $table .= '<div class="table-cell bg-gray-200 text-gray-700 px-4 py-2 text-sm">' . $value['label'] . '</div>';
            $table .= '<div class="table-cell bg-gray-200 text-gray-700 px-4 py-2 text-sm">' . $value['required'] . '</div>';
            $table .= '<div class="table-cell bg-gray-200 text-gray-700 px-4 py-2 text-sm">' . $value['current'] . '</div>';
            $table .= '<div class="table-cell bg-' . ($value['result'] == 'YES' ? 'green' : 'gray') . '-200 text-gray-700 px-4 py-2 text-sm">' . $value['result'] . '</div>';
            $table .= '</div>';
        }
        $table .= '</div>';
        $table .= '</div>';

        return $table;

    }

    public function renderForm($formControls, $formWidth = '1/2', $labelWidth = '1/3', $valueWidth = '2/3')
    {
        $form = '<div class="inline-block w-' . $formWidth . '">';
        foreach ($formControls as $value) {
            $form .= '<div class="flex mt-2">';
            $form .= sprintf('<div class="w-%s flex justify-end items-center pr-10"><span>%s</span></div>', $labelWidth, $value['label']);
            $form .= sprintf(
                '<div class="w-%s flex justify-start items-center pr-10"><input class="border py-2 px-3 text-grey-darkest w-full" type="text" name="%s" value="%s" /></div>',
                $valueWidth, $value['name'], $value['value'] ?? ''
            );
            $form .= '</div>';
        }
        $form .= '</div>';
        return $form;
    }

    public function renderSteps()
    {
        $steps = '<div class="flex mt-10 step text-center">';
        $currentStep = $this->currentStep();
        foreach ($this->steps as $key => $value) {
            $steps .= sprintf('<div class="flex-1 %s">', $currentStep > $key + 1 ? 'text-green-500' : ($currentStep < $key + 1 ? 'text-gray-500' : ''));
            $steps .= sprintf('<div>第%s步</div>', $key + 1);
            $steps .= sprintf('<div>%s</div>', $value);
            $steps .= '</div>';
        }
        $steps .= '</div>';
        return $steps;
    }

    public function listEnvFormControls()
    {
        $envExampleFile = ROOT_PATH . ".env.example";
        $envExampleData = readEnvFile($envExampleFile);
        $envFile = ROOT_PATH . '.env';
        $envData = [];
        if (file_exists($envFile) && is_readable($envFile)) {
            //already exists, read it ,and merge
            $envData = readEnvFile($envFile);
        }
        $mergeData = array_merge($envExampleData, $envData);
        $formControls = [];
        foreach ($mergeData as $key => $value) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
            }
            $formControls[] = [
                'label' => $key,
                'name' => $key,
                'value' => $value,
            ];
        }

        return $formControls;
    }

    public function createAdministrator($username, $email, $password, $confirmPassword)
    {
        if (!validusername($username)) {
            throw new \InvalidArgumentException("Innvalid username: $username");
        }
        $email = htmlspecialchars(trim($email));
        $email = safe_email($email);
        if (!check_email($email)) {
            throw new \InvalidArgumentException("Innvalid email: $email");
        }
        $res = sql_query("SELECT id FROM users WHERE email=" . sqlesc($email));
        $arr = mysql_fetch_row($res);
        if ($arr) {
            throw new \InvalidArgumentException("The email address: $email is already in use");
        }
        if (mb_strlen($password) < 6 || mb_strlen($password) > 40) {
            throw new \InvalidArgumentException("Innvalid password: $password, it should be more than 6 character and less than 40 character");
        }
        if ($password != $confirmPassword) {
            throw new \InvalidArgumentException("confirmPassword: $confirmPassword != password");
        }
        $setting = get_setting('main');
        $secret = mksecret();
        $passhash = md5($secret . $password . $secret);
        $insert = [
            'username' => $username,
            'passhash' => $passhash,
            'secret' => $secret,
            'email' => $email,
            'stylesheet' => $setting['defstylesheet'],
            'class' => 16,
            'status' => 'confirmed',
            'added' => date('Y-m-d H:i:s'),
        ];
        $this->doLog("insert user: " . json_encode($insert));
        return DB::insert('users', $insert);
    }

    public function createEnvFile($data)
    {
        $envExampleFile = ROOT_PATH . ".env.example";
        $envExampleData = readEnvFile($envExampleFile);
        $envFile = ROOT_PATH . ".env";
        $newData = [];
        if (file_exists($envFile) && is_readable($envFile)) {
            //already exists, read it ,and merge post data
            $newData = readEnvFile($envFile);
        }
        foreach ($envExampleData as $key => $value) {
            if (isset($data[$key])) {
                $value = trim($data[$key]);
            }
            $newData[$key] = $value;
        }
        unset($key, $value);
        mysql_connect($newData['MYSQL_HOST'], $newData['MYSQL_USERNAME'], $newData['MYSQL_PASSWORD'], $newData['MYSQL_DATABASE'], $newData['MYSQL_PORT']);
        if (extension_loaded('redis') && !empty($newData['REDIS_HOST'])) {
            $redis = new \Redis();
            $redis->connect($newData['REDIS_HOST'], $newData['REDIS_PORT'] ?: 6379);
            if (isset($newData['REDIS_DATABASE'])) {
                if (!ctype_digit($newData['REDIS_DATABASE']) || $newData['REDIS_DATABASE'] < 0 || $newData['REDIS_DATABASE'] > 15) {
                    throw new \InvalidArgumentException("invalid redis database: " . $newData['redis_database']);
                }
                $redis->select($newData['REDIS_DATABASE']);
            }
        }
        $content = "";
        foreach ($newData as $key => $value) {
            $content .= "{$key}={$value}\n";
        }
        $fp = @fopen($envFile, 'w');
        if ($fp === false) {
            throw new \RuntimeException("can't open env file, make sure php has permission to create file at: " . ROOT_PATH);
        }
        fwrite($fp, $content);
        fclose($fp);
        $this->doLog("create env file: $envFile with content: \n $content");
        return true;
    }

    public function listShouldCreateTable()
    {
        $existsTable = $this->listExistsTable();
        $tableCreate = $this->listAllTableCreate();
        $shouldCreateTable = [];
        foreach ($tableCreate as $table => $sql) {
            if (in_array($table, $existsTable)) {
                continue;
            }
            $shouldCreateTable[$table] = $sql;
        }
        return $shouldCreateTable;
    }

    public function createTable(array $createTable)
    {
        foreach ($createTable as $table => $sql) {
            $this->doLog("create table: $table \n $sql");
            sql_query($sql);
        }
        return true;
    }

    public function saveSettings($settings)
    {
        foreach ($settings as $prefix => &$group) {
            $this->doLog("[SAVE SETTING], prefix: $prefix, nameAndValues: " . json_encode($group));
            saveSetting($prefix, $group);
        }
    }

    public function createSymbolicLinks($symbolicLinks)
    {
        foreach ($symbolicLinks as $path) {
            $linkName = ROOT_PATH . 'public/' . basename($path);
            if (is_dir($linkName)) {
                $this->doLog("path: $linkName already exits, skip create symbolic link $linkName -> $path");
                continue;
            }
            $linkResult = symlink($path, $linkName);
            if ($linkResult === false) {
                throw new \RuntimeException("can't not make symbolic link:  $linkName -> $path");
            }
            $this->doLog("success make symbolic link: $linkName -> $path");
        }
        return true;
    }
}