#!/bin/sh
#
# Gradle start up script for UN*X
#
APP_NAME="Gradle"
APP_BASE_NAME=`basename "$0"`
APP_HOME="`dirname \"$0\"`"
APP_HOME="`( cd \"$APP_HOME\" && pwd )`"
CLASSPATH=$APP_HOME/gradle/wrapper/gradle-wrapper.jar

# Determine the Java command to use
if [ -n "$JAVA_HOME" ] ; then
    JAVACMD="$JAVA_HOME/bin/java"
elif [ -x "/opt/homebrew/opt/openjdk@21/bin/java" ]; then
    # Prefer Java 21 from Homebrew (compatible with Android Gradle Plugin)
    JAVACMD="/opt/homebrew/opt/openjdk@21/bin/java"
else
    JAVACMD="java"
fi

exec "$JAVACMD" $DEFAULT_JVM_OPTS $JAVA_OPTS $GRADLE_OPTS \
    "-Dorg.gradle.appname=$APP_BASE_NAME" \
    -classpath "$CLASSPATH" org.gradle.wrapper.GradleWrapperMain "$@"
