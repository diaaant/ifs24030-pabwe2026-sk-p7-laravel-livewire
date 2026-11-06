// Pipeline CI - Aplikasi Inventaris Stok Barang (Laravel 13 + Livewire 4)
//
// Prasyarat image/agent Jenkins:
//   PHP >= 8.3 dengan ext: gd, zip, mbstring, pdo_mysql, pdo_sqlite, xdebug (atau pcov)
//   Composer, Node.js/npm, Trivy, zip
pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    environment {
        APP_ENV = 'testing'
        COMPOSER_NO_INTERACTION = '1'
        XDEBUG_MODE = 'coverage'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Composer Install') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --no-progress'
                sh 'cp -n .env.example .env || true'
                sh 'php artisan key:generate --force'
            }
        }

        stage('Composer Audit') {
            steps {
                // Gagal jika ada kerentanan pada dependensi
                sh 'composer audit --no-interaction'
            }
        }

        stage('NPM Build') {
            steps {
                sh 'npm install --ignore-scripts'
                sh 'npm run build'
            }
        }

        stage('PHPUnit + Coverage Gate 100%') {
            steps {
                sh 'php artisan config:clear'
                sh 'vendor/bin/phpunit --colors=never --coverage-text --coverage-clover=coverage.xml | tee coverage.txt'
                // Gagal jika coverage Classes / Methods / Lines < 100%
                sh 'php scripts/check-coverage.php coverage.xml'
            }
        }

        stage('Trivy Scan') {
            steps {
                sh 'trivy fs --exit-code 1 --severity HIGH,CRITICAL --scanners vuln,secret --skip-dirs vendor,node_modules,storage .'
            }
        }

        stage('Archive') {
            steps {
                sh '''
                    rm -f latest-app.zip
                    zip -r latest-app.zip . \
                        -x "vendor/*" "node_modules/*" ".git/*" ".env" \
                        -x "coverage*" "junit.xml" "events.txt" "phpunit_*.txt" "trivy*" ".phpunit.cache/*" "latest-app.zip" \
                        -x "storage/logs/*" "public/build/*" "public/storage"
                '''
                archiveArtifacts artifacts: 'latest-app.zip', fingerprint: true
            }
        }
    }

    post {
        always {
            archiveArtifacts artifacts: 'coverage.txt, coverage.xml', allowEmptyArchive: true
        }
        failure {
            echo 'Pipeline gagal: periksa audit, coverage (harus 100%) atau hasil Trivy.'
        }
    }
}
