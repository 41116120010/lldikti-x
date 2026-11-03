<?php

namespace Tests\Unit;

use App\Http\Requests\Agenda\UpdateMinutesRequest;
use PHPUnit\Framework\TestCase;

class UpdateMinutesRequestTest extends TestCase
{
    public function test_rules_include_captions_and_existing_captions_validation(): void
    {
        $request = new UpdateMinutesRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('captions', $rules);
        $this->assertSame(['nullable', 'array'], $rules['captions']);
        $this->assertArrayHasKey('captions.*', $rules);
        $this->assertSame(['nullable', 'string', 'max:255'], $rules['captions.*']);

        $this->assertArrayHasKey('existing_captions', $rules);
        $this->assertSame(['nullable', 'array'], $rules['existing_captions']);
        $this->assertArrayHasKey('existing_captions.*', $rules);
        $this->assertSame(['nullable', 'string', 'max:255'], $rules['existing_captions.*']);
    }

    public function test_attributes_include_caption_labels(): void
    {
        $request = new UpdateMinutesRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey('captions.*', $attributes);
        $this->assertArrayHasKey('existing_captions.*', $attributes);
    }
}
