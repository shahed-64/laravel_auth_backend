<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class BackupController extends Controller
{
    public function takeBackup()
    {
        // ব্যাকআপ নিতে সময় ও মেমোরি বেশি লাগতে পারে
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        try {
            $databaseName = config('database.connections.mysql.database');
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $databaseName;

            $sqlScript = "";

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                // টেবিল স্ট্রাকচার রিড করা
                $createTableQuery = DB::select("SHOW CREATE TABLE `$tableName`");
                $sqlScript .= "\n\n" . $createTableQuery[0]->{'Create Table'} . ";\n\n";

                // টেবিলের ডাটা রিড করা
                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $columns = array_keys($rowArray);
                    $values = array_values($rowArray);

                    $escapedValues = array_map(function ($value) {
                        if (is_null($value)) return 'NULL';
                        return "'" . addslashes($value) . "'";
                    }, $values);

                    $sqlScript .= "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
                }
            }

            // ব্যাকআপ ফাইলের নাম এবং ফোল্ডার পাথ নির্ধারণ
            $fileName = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
            $directory = storage_path('app/backups');

            // ফোল্ডার না থাকলে উইন্ডোজ বা লিনাক্স ফ্রেন্ডলি উপায়ে তৈরি করা
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;
            file_put_contents($filePath, $sqlScript);

            return response()->json([
                'status' => 'success',
                'message' => 'Backup created successfully!',
                'file' => $fileName
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
