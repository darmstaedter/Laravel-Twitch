<?php

namespace romanzipp\Twitch\Tests\Api;

use BadMethodCallException;
use romanzipp\Twitch\Tests\TestCases\ApiTestCase;

class VideosTest extends ApiTestCase
{
    public function testGetVideosMissingParameters()
    {
        $this->expectException(BadMethodCallException::class);

        $this->twitch()->getVideos([]);
    }

    public function testGetVideosById()
    {
        $this->registerResult(
            $result = $this->twitch()->getVideosById(327720797)
        );

        $this->assertTrue($result->success());
        $this->assertNotEmpty($result->data());
        $this->assertHasProperties([
            'id', 'user_id', 'user_name', 'title', 'description', 'created_at', 'published_at', 'url',
            'thumbnail_url', 'viewable', 'view_count', 'language', 'type', 'duration',
        ], $result->shift());
        $this->assertEquals(327720797, (int) $result->shift()->id);
    }

    public function testGetVideosByUser()
    {
        $this->registerResult(
            $result = $this->twitch()->getVideosByUser(12826)
        );

        $this->assertTrue($result->success());
        $this->assertNotEmpty($result->data());
        $this->assertHasProperties([
            'id', 'user_id', 'user_name', 'title', 'description', 'created_at', 'published_at', 'url',
            'thumbnail_url', 'viewable', 'view_count', 'language', 'type', 'duration',
        ], $result->shift());
        $this->assertEquals(12826, (int) $result->shift()->user_id);
    }

    public function testGetVideosByGame()
    {
        $this->registerResult(
            $result = $this->twitch()->getVideosByGame(511224)
        );

        $this->assertTrue($result->success());
        $this->assertNotEmpty($result->data());
        $this->assertHasProperties([
            'id', 'user_id', 'user_name', 'title', 'description', 'created_at', 'published_at', 'url',
            'thumbnail_url', 'viewable', 'view_count', 'language', 'type', 'duration',
        ], $result->shift());
        // Video payload has no game_id ?!?!?!?!?!?!?!
    }
}
