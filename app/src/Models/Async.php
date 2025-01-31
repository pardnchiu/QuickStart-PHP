<?php

namespace Models;

use React\Promise;
use React\EventLoop\Loop;

class Async
{
    public static function run($tasks)
    {
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

        $sortedFlow = self::topologicalSort($flow);

        $tasks = self::createTasks($methods, $flow, $sortedFlow, $loop);

        return Promise\all($tasks)
            ->then(function ($results) use ($loop) {
                $loop->run();
                return $results;
            })
            ->catch(function ($error) {
                throw $error;
            });
    }

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

    private static function createTasks($methods, $flow, $sortedKeys, $loop)
    {
        $tasks = [];
        $resolvedTasks = [];

        foreach ($sortedKeys as $taskKey) {
            $dependentTasks = isset($flow[$taskKey]) ? $flow[$taskKey] : [];
            $tasks[$taskKey] = self::createTask($methods, $taskKey, $dependentTasks, $resolvedTasks, $loop);
        }

        return $tasks;
    }

    // 創建單個任務
    private static function createTask($methods, $taskKey, $tasks, &$resolvedTasks, $loop)
    {
        if (isset($resolvedTasks[$taskKey])) {
            return $resolvedTasks[$taskKey];
        }

        $deferred = new Promise\Deferred();

        $taskPromises = [];

        foreach ($tasks as $task) {
            if (!isset($resolvedTasks[$task])) {
                $resolvedTasks[$task] = self::createTask($methods, $task, [], $resolvedTasks, $loop);
            }
            $taskPromises[] = $resolvedTasks[$task];
        }

        Promise\all($taskPromises)->then(function () use ($methods, $taskKey, $deferred) {
            try {
                $result = call_user_func($methods[$taskKey]);
                $deferred->resolve($result);
            } catch (\Exception $e) {
                $deferred->reject($e);
            }
        })->catch(function ($error) use ($deferred) {
            $deferred->reject($error);
        });

        $resolvedTasks[$taskKey] = $deferred->promise();

        return $resolvedTasks[$taskKey];
    }
}
