<!-- File: /app/View/Posts/index.ctp -->
<h1>Blog posts</h1>
<table>
    <tr>
        <th>Id</th>
        <th>Title</th>
        <th>Created</th>
    </tr>

<!-- Here is where we loop through our $posts array, printing out post info -->

    <?php foreach ($books as $book): ?>
    <tr>
        <td><?php echo $book['Book']['Id']; ?></td>
        <td>
            <?php echo $this->Html->link($book['Book']['title'],
array('controller' => 'books', 'action' => 'view', $book['Book']['Id'])); ?>
        </td>
        <td><?php echo $book['Book']['isbn']; ?></td>
    </tr>
    <?php endforeach; ?>
</table>
