<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IaPromptExecution extends Model
{
    use HasFactory;

    protected $table = 'ia_prompt_executions';

    protected $fillable = [
        'customer_id',
        'prompt_key',
        'category',
        'subcategory',
        'model_name',
        'ai_provider',
        'ai_url_used',
        'variables_json',
        'prompt_text',
        'response_json',
        'response_text',
        'tasker_id',
        'status',
        'error_message',
        'http_status',
        'retry_count',
        'max_retries',
        'last_polled_at',
        'next_poll_at',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'variables_json' => 'array',
        'response_json' => 'array',
        'http_status' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'last_polled_at' => 'datetime',
        'next_poll_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
