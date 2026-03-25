<?php

namespace App\Services;

use App\Models\AdminLog;
use Illuminate\Http\Request;

class AdminLogService
{
    public function log(
        int $adminId,
        string $action,
        string $model = null,
        int $modelId = null,
        array $oldData = null,
        array $newData = null,
        string $ip = null
    ): AdminLog {
        return AdminLog::create([
            'admin_id'   => $adminId,
            'action'     => $action,
            'model'      => $model,
            'model_id'   => $modelId,
            'old_data'   => $oldData,
            'new_data'   => $newData,
            'ip_address' => $ip,
        ]);
    }

    public function logFromRequest(Request $request, string $action, string $model = null, int $modelId = null, array $old = null, array $new = null): AdminLog
    {
        return $this->log(
            $request->user()->id,
            $action,
            $model,
            $modelId,
            $old,
            $new,
            $request->ip()
        );
    }
}
