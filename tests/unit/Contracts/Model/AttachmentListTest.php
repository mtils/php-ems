<?php
/**
 *  * Created by mtils on 18.12.17 at 13:13.
 **/

namespace Ems\Contracts\Model;



use Ems\Model\OrmAttachment;
use Ems\TestCase;

class AttachmentListTest extends TestCase
{
    public function test_new_instance()
    {
        $this->assertInstanceOf(AttachmentList::class, $this->newList());
    }

    public function test_where()
    {
        $list = $this->newList();

        $attachments = [
            [
                'id' => 1,
                'width' => 800,
                'height' => 600,
                'url' => 'http://bla.de/image-1.png',
                'mimetype' => 'image/png',
                'role' => Attachment::DISPLAY
            ],
            [
                'id' => 2,
                'width' => 800,
                'height' => 600,
                'url' => 'http://bla.de/image-2.png',
                'mimetype' => 'image/png',
                'role' => Attachment::DISPLAY
            ],
            [
                'id' => 3,
                'width' => 800,
                'height' => 600,
                'url' => 'http://bla.de/image-3.png',
                'mimetype' => 'image/png',
                'role' => Attachment::FRONT_COVER
            ],
            [
                'id' => 4,
                'width' => 800,
                'height' => 600,
                'url' => 'http://bla.de/document-1.pdf',
                'mimetype' => 'application/pdf',
                'role' => Attachment::MANUAL
            ],
            [
                'id' => 5,
                'width' => 800,
                'height' => 600,
                'url' => 'http://bla.de/image-4.png',
                'mimetype' => 'image/png',
                'role' => Attachment::SIZING_TABLE
            ]
        ];

        foreach ($attachments as $attachment) {
            $list->append(new OrmAttachment($attachment));
        }

        $this->assertCount(5, $attachments);

        $result = $list->where('role', Attachment::DISPLAY);

        $this->assertCount(2, $result);

        $this->assertCount(4, $list->where('type', 'image/png'));
        $this->assertCount(1, $list->where('type', 'application/pdf'));
    }

    public function test_where_throws_exception_if_filter_not_supported()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $this->newList()->where('foo', 'bar');
    }
    protected function newList($attachments=[])
    {
        return new AttachmentList($attachments);
    }
}