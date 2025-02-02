<?php

namespace App\Models;

use React\Promise;
use React\EventLoop\Loop;

class Async
{
    // 執行異步任務
    public static function run($tasks)
    {
        // 獲取事件循環實例
        $loop = Loop::get();

        $flow = [];
        $methods = [];

        foreach ($tasks as $taskKey => $task) {
            $methods[$taskKey] = $task['method'];

            if (empty($task['tasks'])) {
                $flow[$taskKey] = [];
            } else {
                $flow[$taskKey] = $task['tasks'];
            };
        };

        // 對任務流程進行拓撲排序，確保正確的執行順序
        $sortedFlow = self::topologicalSort($flow);

        // 創建所有的任務，並根據排序好的流程和查詢參數
        $tasks = self::createTasks($methods, $flow, $sortedFlow, $loop);

        // 使用 Promise.all 等待所有任務完成
        return Promise\all($tasks)
            ->then(function ($results) use ($loop) {
                // 當所有任務完成後，運行事件循環
                $loop->run();
                // 返回所有任務的結果
                return $results;
            })
            ->catch(function ($error) {
                // 捕獲並拋出異常
                throw $error;
            });
    }

    // 排序任務流程
    private static function topologicalSort($flow)
    {
        $sorted = [];
        $visited = [];
        $temporary = [];

        foreach ($flow as $taskKey => $tasks) {
            if (is_int($taskKey)) {
                $taskKey = $tasks;
                $tasks = [];
            }

            if (!isset($visited[$taskKey])) {
                self::visit($taskKey, $flow, $visited, $sorted, $temporary);
            }
        }

        return array_reverse($sorted);
    }

    private static function visit($taskKey, $flow, &$visited, &$sorted, &$temporary)
    {
        if (isset($temporary[$taskKey])) {
            throw new \Exception("Circular dependency detected: " . $taskKey);
        }

        if (!isset($visited[$taskKey])) {
            $temporary[$taskKey] = true;

            if (isset($flow[$taskKey])) {
                foreach ($flow[$taskKey] as $task) {
                    self::visit($task, $flow, $visited, $sorted, $temporary);
                }
            }

            unset($temporary[$taskKey]);
            $visited[$taskKey] = true;
            $sorted[] = $taskKey;
        }
    }


    // 創建任務
    private static function createTasks($methods, $flow, $sortedKeys, $loop)
    {
        // 初始化任務數組
        $tasks = [];
        // 初始化已解決的任務數組
        $resolvedTasks = [];

        // 遍歷排序後的任務鍵
        foreach ($sortedKeys as $taskKey) {
            // 獲取該任務的前置任務
            $dependentTasks = isset($flow[$taskKey]) ? $flow[$taskKey] : [];
            // 創建並儲存該任務
            $tasks[$taskKey] = self::createTask($methods, $taskKey, $dependentTasks, $resolvedTasks, $loop);
        }

        // 返回創建的所有任務
        return $tasks;
    }

    // 創建單個任務
    private static function createTask($methods, $taskKey, $tasks, &$resolvedTasks, $loop)
    {
        // 如果該任務已經被解決，直接返回已解決的任務
        if (isset($resolvedTasks[$taskKey])) {
            return $resolvedTasks[$taskKey];
        }

        // 創建一個新的 Promise 延遲對象
        $deferred = new Promise\Deferred();

        // 解決前置任務並創建相應的任務 Promise
        $taskPromises = [];
        foreach ($tasks as $task) {
            // 如果前置任務尚未解決，創建前置任務
            if (!isset($resolvedTasks[$task])) {
                $resolvedTasks[$task] = self::createTask($methods, $task, [], $resolvedTasks, $loop);
            }
            // 將前置任務的 Promise 添加到數組中
            $taskPromises[] = $resolvedTasks[$task];
        }

        // 當所有前置任務解決後，執行當前任務
        Promise\all($taskPromises)->then(function () use ($methods, $taskKey, $deferred) {
            try {
                // 調用當前任務的方法並返回結果
                $result = call_user_func($methods[$taskKey]);
                // 解決當前任務的 Promise
                $deferred->resolve($result);
            } catch (\Exception $e) {
                // 捕獲異常並拒絕當前任務的 Promise
                $deferred->reject($e);
            }
        })->catch(function ($error) use ($deferred) {
            // 捕獲異常並拒絕當前任務的 Promise
            $deferred->reject($error);
        });

        // 將當前任務的 Promise 存儲在已解決的任務數組中
        $resolvedTasks[$taskKey] = $deferred->promise();

        // 返回當前任務的 Promise
        return $resolvedTasks[$taskKey];
    }
}
