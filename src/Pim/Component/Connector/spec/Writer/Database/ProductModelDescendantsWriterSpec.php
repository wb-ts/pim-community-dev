<?php

namespace spec\Pim\Component\Connector\Writer\Database;

use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\StorageUtils\Cache\EntityManagerClearerInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Pim\Component\Connector\Writer\Database\ProductModelDescendantsWriter;
use PhpSpec\ObjectBehavior;

class ProductModelDescendantsWriterSpec extends ObjectBehavior
{
    function let(StepExecution $stepExecution, SaverInterface $descendantsSaver, EntityManagerClearerInterface $cacheClearer)
    {
        $this->beConstructedWith($descendantsSaver, $cacheClearer);
        $this->setStepExecution($stepExecution);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ProductModelDescendantsWriter::class);
    }

    function it_handles_product_model_descendants(
        $descendantsSaver,
        $stepExecution,
        ProductModelInterface $productModel1,
        ProductModelInterface $productModel2
    ) {
        $descendantsSaver->save($productModel1)->shouldBeCalled();
        $descendantsSaver->save($productModel2)->shouldBeCalled();

        $stepExecution->incrementSummaryInfo('process')->shouldBeCalledTimes(2);

        $this->write([$productModel1, $productModel2]);
    }
}
