FROM phpstorm/php-cli:8.2-xdebug3.2

RUN docker-php-ext-install mysqli pdo pdo_mysql