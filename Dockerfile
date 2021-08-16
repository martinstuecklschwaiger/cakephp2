# FROM php:8.0.9-cli-alpine3.14
FROM php:7.4.22-cli-alpine3.14

RUN apk update && apk add bash vim
RUN echo "PS1='🐳  \[\033[1;36m\]\h \[\033[1;34m\]\W\[\033[0;35m\] \[\033[1;36m\]# \[\033[0m\]'" > ~/.bashrc

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

COPY . /cakephp

WORKDIR /cakephp

RUN composer require --dev phpunit/phpunit:^9 --update-with-dependencies --no-progress

RUN composer install --no-progress

WORKDIR /cakephp/app

RUN composer require --dev phpunit/phpunit:^9 --update-with-dependencies --no-progress

RUN composer install --no-progress

WORKDIR /cakephp

CMD [ "bash" ]

#CMD [ "./vendors/bin/phpunit" ]
