all: build/release/net.nosial.loglib2.ncc build/debug/net.nosial.loglib2.ncc
build/release/net.nosial.loglib2.ncc:
	ncc build --configuration release --log-level debug
build/debug/net.nosial.loglib2.ncc:
	ncc build --configuration debug --log-level debug


docs:
	phpdoc --config phpdoc.dist.xml

clean:
	rm build/release/net.nosial.loglib2.ncc
	rm build/debug/net.nosial.loglib2.ncc
	rm target/docs
	rm target/cache

.PHONY: all install clean docs