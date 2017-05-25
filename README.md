# graph_to_tiles - Консольная утилита для отрисовки уложенных графов в виде тайлов

Данная версия скрипта является ДЕМО и не для продакшена! Для работы необходима php библиотека Image Magick.

php graph_to_tiles.php les_miserables.gexf les_miserables.json, где les_miserables.gexf - файл графа, а les_miserables.json файл настроек отрисовщика. Цвета вершин задаются в файле gexf.

php graph_to_svg.php les_miserables.gexf les_miserables.json result.svg - отрисовка единого svg файла.
