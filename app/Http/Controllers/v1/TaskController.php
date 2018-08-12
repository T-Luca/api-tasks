<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
/**
 * Class TaskController
 *
 * @package App\Http\Controllers\v1
 */
class TaskController extends Controller
{
    /**
     * Get tasks list
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTasks()
    {
        try {
            $tasks = DB::table('tasks')->paginate(10);

            return $this->returnSuccess($tasks);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    /**
     * Create a task
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTask(Request $request)
    {
        try {
            $rules = [
                'name' => 'required',
                'description' =>'required',
                'status' => 'required',
                'assign' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules);

            if (!$validator->passes()) {
                return $this->returnBadRequest('Please fill all required fields');
            }

            //user id logged
            $user = $this->validateSession();

            $task = new Task();

            $task->name = $request->name;
            $task->description = $request->description;
            $task->status = $request->status;
            $task->user_id = $user->id;
            $task->assign = $request->assign;

            $task->save();

            return $this->returnSuccess($task);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    /**
     * Update a task
     *
     * @param Request $request
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTask(Request $request, $id)
    {
        try {
            $task = Task::find($id);

            if ($request->has('name')) {
                $task->name = $request->name;
            }


            if ($request->has('description')) {
                $task->description = $request->description;
            }

            if ($request->has('status')) {
                $task->status = $request->status;
            }

            if ($request->has('assign')) {
                $task->assign = $request->assign;
            }

            $task->save();

            return $this->returnSuccess($task);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    /**
     * Delete a task
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTask($id)
    {
        try {
            $task = Task::find($id);

            $task->delete();

            return $this->returnSuccess();
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
}